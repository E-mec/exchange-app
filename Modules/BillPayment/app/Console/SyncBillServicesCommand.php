<?php

namespace Modules\BillPayment\app\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\BillPayment\app\Resolvers\BillProviderResolver;
use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Models\BillService;
use Modules\BillPayment\Models\BillServiceVariation;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SyncBillServicesCommand extends Command
{
    protected $signature = 'bills:sync
                            {--provider=all : vtpass | buypower | baxi | all}
                            {--variations-only : skip service sync, only sync variations}
                            {--dry-run : preview changes without writing to DB}';

    protected $description = 'Sync bill services and variation codes from providers';

    public function __construct(private readonly BillProviderResolver $resolver)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $providerOption = $this->option('provider');
        $variationsOnly = $this->option('variations-only');
        $dryRun         = $this->option('dry-run');

        $providers = $providerOption === 'all'
            ? BillProviderEnum::cases()
            : [BillProviderEnum::from($providerOption)];

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        }

        foreach ($providers as $providerEnum) {
            $this->info("\n[{$providerEnum->value}] Starting sync...");

            $provider = $this->resolver->resolve($providerEnum);

            // ── Step 1: Sync services ──────────────────────
            if (! $variationsOnly) {
                $this->info('  Fetching services...');
                $services = $provider->getServices();
                $this->info('  Found ' . count($services) . ' service(s).');

                foreach ($services as $dto) {
                    $slug = Str::slug("{$dto->provider->value}-{$dto->providerServiceId}");

                    $row = [
                        'slug'                => $slug,
                        'name'               => $dto->name,
                        'type'               => $dto->type->value,
                        'provider'           => $dto->provider->value,
                        'provider_service_id'=> $dto->providerServiceId,
                        'has_variations'     => $dto->hasVariations,
                        'requires_validation'=> $dto->requiresValidation,
                        'min_amount'         => $dto->minAmount,
                        'max_amount'         => $dto->maxAmount,
                        'image_url'          => $dto->imageUrl,
                        'is_active'          => true,
                        'updated_at'         => now(),
                        'created_at'         => now(),
                    ];

                    if ($dryRun) {
                        $this->line("    [DRY] Would upsert service: {$dto->name} ({$dto->providerServiceId})");
                        continue;
                    }

                    BillService::upsert(
                        [$row],
                        uniqueBy: ['provider', 'provider_service_id'],
                        update: [
                            'name', 'has_variations', 'requires_validation',
                            'min_amount', 'max_amount', 'image_url', 'updated_at',
                        ]
                    );
                }
            }

            // ── Step 2: Sync variations ────────────────────
            $this->info('  Syncing variations...');

            BillService::fromProvider($providerEnum)
                ->where('has_variations', true)
                ->each(function (BillService $service) use ($provider, $dryRun) {
                    $this->line("    [{$service->provider_service_id}] Fetching variations...");

                    $variations = $provider->getVariations($service->provider_service_id);

                    if (empty($variations)) {
                        $this->warn("    [{$service->provider_service_id}] No variations returned — skipping.");
                        return;
                    }

                    $this->line('    Found ' . count($variations) . ' variation(s).');

                    if ($dryRun) {
                        foreach ($variations as $dto) {
                            $this->line("      [DRY] {$dto->name} — ₦{$dto->amount} ({$dto->variationCode})");
                        }
                        return;
                    }

                    $rows = collect($variations)->map(fn ($dto, $index) => [
                        'bill_service_id' => $service->id,
                        'name'            => $dto->name,
                        'variation_code'  => $dto->variationCode,
                        'amount'          => $dto->amount,
                        'validity'        => $dto->validity,
                        'is_fixed_price'  => $dto->isFixedPrice,
                        'is_active'       => true,
                        'sort_order'      => $index,
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ])->toArray();

                    BillServiceVariation::upsert(
                        $rows,
                        uniqueBy: ['bill_service_id', 'variation_code'],
                        update: ['name', 'amount', 'validity', 'is_fixed_price', 'sort_order', 'is_active', 'updated_at']
                    );

                    // Deactivate any variation codes no longer returned by provider
                    $liveCodes = collect($variations)->pluck('variationCode');

                    $deactivated = BillServiceVariation::where('bill_service_id', $service->id)
                        ->whereNotIn('variation_code', $liveCodes)
                        ->update(['is_active' => false]);

                    if ($deactivated > 0) {
                        $this->warn("    Deactivated {$deactivated} removed variation(s).");
                    }
                });
        }

        $this->info("\nSync complete.");
        return self::SUCCESS;
    }
}
