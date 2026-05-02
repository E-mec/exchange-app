<?php

namespace Modules\BillPayment\app\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\BillPayment\app\Interfaces\BillProviderInterface;
use Modules\BillPayment\dtos\BillPaymentData;
use Modules\BillPayment\dtos\ProviderServiceDto;
use Modules\BillPayment\dtos\ProviderVariationDto;
use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Enums\BillTypeEnum;

class VTPassProvider implements BillProviderInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $publicKey;
    private string $secretKey;

// Maps VTPass identifier strings to your BillTypeEnum cases
    private const TYPE_MAP = [
        'airtime' => BillTypeEnum::AIRTIME,
        'data' => BillTypeEnum::DATA,
        'tv-subscription' => BillTypeEnum::TV,
        'internet' => BillTypeEnum::INTERNET,
        'betting' => BillTypeEnum::BETTING,
    ];

// Services under each identifier that need recipient validation
    private const REQUIRES_VALIDATION = [
        'dstv', 'gotv', 'startimes', 'showmax',
        'smile-direct', 'spectranet', 'swift', 'ipnx',
        'bet9ja', 'sportybet', 'betway', '1xbet',
    ];

    public function __construct()
    {
        $config = config('billpayment.providers.vtpass');
        $this->baseUrl = $config['base_url'];
        $this->apiKey = $config['api_key'];
        $this->publicKey = $config['public_key'];
        $this->secretKey = $config['secret_key'];
    }

// ── Catalogue discovery ────────────────────────────────

    public function getServices(): array
    {
        $services = [];

        foreach (self::TYPE_MAP as $identifier => $type) {
            try {
                $response = Http::withHeaders($this->headers())
                    ->get("{$this->baseUrl}/services", [
                        'identifier' => $identifier,
                    ]);

                if (!$response->successful()) {
                    Log::warning("VTPass: failed to fetch services for [{$identifier}]", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue;
                }

                foreach ($response->json('content', []) as $item) {
                    $services[] = new ProviderServiceDto(
                        name: $item['name'],
                        providerServiceId: $item['serviceID'],
                        type: $type,
                        provider: BillProviderEnum::VTPASS,
                        hasVariations: ($item['product_type'] ?? '') === 'fix',
                        requiresValidation: in_array($item['serviceID'], self::REQUIRES_VALIDATION),
                        minAmount: isset($item['minimium_amount']) ? (float)$item['minimium_amount'] : null,
                        maxAmount: isset($item['maximum_amount']) ? (float)$item['maximum_amount'] : null,
                        imageUrl: $item['image'] ?? null,
                    );
                }
            } catch (\Throwable $e) {
                Log::error("VTPass: exception fetching services for [{$identifier}]", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $services;
    }

    public function getVariations(string $providerServiceId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/service-variations", [
                'serviceID' => $providerServiceId,
            ]);

        if (!$response->successful()) {
            Log::warning("VTPass: failed to fetch variations for [{$providerServiceId}]");
            return [];
        }

// VTPass returns either 'variations' or 'varations' (their typo — handle both)
        $raw = $response->json('content.variations')
            ?? $response->json('content.varations')
            ?? [];

        return collect($raw)->map(fn($v) => new ProviderVariationDto(
            name: $v['name'],
            variationCode: $v['variation_code'],
            amount: (float)$v['variation_amount'],
            isFixedPrice: ($v['fixedPrice'] ?? 'Yes') === 'Yes',
            validity: $this->extractValidity($v['name']),
        ))->toArray();
    }

// ── Transactions ───────────────────────────────────────

    public function validateRecipient(string $providerServiceId, string $recipient): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/merchant-verify", [
                'serviceID' => $providerServiceId,
                'billersCode' => $recipient,
            ]);

        return $response->json();
    }

    public function purchase(BillPaymentData $dto): array
    {
        $payload = [
            'request_id' => $dto->reference,
            'serviceID' => $dto->serviceId,
            'billersCode' => $dto->recipient,
            'amount' => $dto->amount,
            'phone' => $dto->meta['phone'] ?? $dto->recipient,
        ];

        if ($dto->variationCode) {
            $payload['variation_code'] = $dto->variationCode;
        }

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/pay", $payload);

        return $response->json();
    }

    public function queryStatus(string $requestId): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/requery", [
                'request_id' => $requestId,
            ]);

        return $response->json();
    }

    public function verifyWebhook(Request $request): bool
    {
// VTPass signs webhook payloads with your secret key
        $signature = $request->header('X-VTpass-Signature');
        $expected = hash_hmac('sha512', $request->getContent(), $this->secretKey);

        return hash_equals($expected, (string)$signature);
    }

// ── Helpers ────────────────────────────────────────────

    private function headers(): array
    {
        return [
            'api-key' => $this->apiKey,
            'public-key' => $this->publicKey,
            'secret-key' => $this->secretKey,
        ];
    }

    private function extractValidity(string $planName): ?string
    {
// Extract "30 days", "1 month", "1 year" from plan name string
        if (preg_match('/(\d+)\s*(day|month|year)s?/i', $planName, $matches)) {
            return "{$matches[1]} {$matches[2]}";
        }
        return null;
    }
}
