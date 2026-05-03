<?php

namespace Modules\BillPayment\app\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\BillPayment\app\Interfaces\BillProviderInterface;
use Modules\BillPayment\dtos\BillPaymentData;
use Modules\BillPayment\dtos\ProviderServiceDto;
use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Enums\BillTypeEnum;

class BaxiProvider implements BillProviderInterface
{
    private string $baseUrl;
    private string $apiKey;

    private const SERVICES = [
        ['serviceID' => 'lagos-water', 'name' => 'Lagos Water Corporation', 'type' => BillTypeEnum::WATER, 'active' => true],
        ['serviceID' => 'abuja-water', 'name' => 'FCT Water Board',         'type' => BillTypeEnum::WATER, 'active' => false],
    ];

    public function __construct()
    {
        $config        = config('billpayment.providers.baxi');
        $this->baseUrl = $config['base_url'];
        $this->apiKey  = $config['api_key'];
    }

    public function getServices(): array
    {
        return collect(self::SERVICES)
            ->filter(fn ($s) => $s['active'])
            ->map(fn ($s) => new ProviderServiceDto(
                name:               $s['name'],
                providerServiceId:  $s['serviceID'],
                type:               $s['type'],
                provider:           BillProviderEnum::BAXI,
                hasVariations:      false,
                requiresValidation: true,
                minAmount:          500,
                maxAmount:          null,
            ))->values()->toArray();
    }

    public function getVariations(string $providerServiceId): array
    {
        return [];
    }

    public function validateRecipient(string $providerServiceId, string $recipient): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/bills/validate", [
                'service_type' => $providerServiceId,
                'account_id'   => $recipient,
            ]);

        return $response->json();
    }

    public function purchase(BillPaymentData $dto): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/bills/payment", [
                'agentId'        => $dto->reference,
                'agentReference' => $dto->reference,
                'service_type'   => $dto->serviceId,
                'amount'         => $dto->amount,
                'account_id'     => $dto->recipient,
                'phone'          => $dto->meta['phone'] ?? '',
            ]);

        return $response->json();
    }

    public function queryStatus(string $requestId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/bills/transaction/{$requestId}");

        return $response->json();
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('x-baxi-signature');
        $expected  = hash_hmac('sha256', $request->getContent(), $this->apiKey);
        return hash_equals($expected, (string) $signature);
    }

    public function isSuccessful(array $response): bool
    {
        return ($response['code'] ?? '') === '200';
    }

    private function headers(): array
    {
        return ['x-api-key' => $this->apiKey];
    }


}
