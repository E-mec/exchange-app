<?php

namespace Modules\BillPayment\app\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\BillPayment\app\Interfaces\BillProviderInterface;
use Modules\BillPayment\dtos\BillPaymentData;
use Modules\BillPayment\dtos\ProviderServiceDto;
use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Enums\BillTypeEnum;

class BuyPowerProvider implements BillProviderInterface
{
    private string $baseUrl;
    private string $token;

    // BuyPower has no service discovery endpoint.
    // Services are defined statically from their docs.
    private const SERVICES = [
        ['serviceID' => 'IKEDC',  'name' => 'Ikeja Electric (IKEDC)',        'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'EKEDC',  'name' => 'Eko Electric (EKEDC)',          'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'AEDC',   'name' => 'Abuja Electric (AEDC)',         'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'PHED',   'name' => 'Port Harcourt Electric (PHED)', 'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'EEDC',   'name' => 'Enugu Electric (EEDC)',         'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'KEDCO',  'name' => 'Kano Electric (KEDCO)',         'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'IBEDC',  'name' => 'Ibadan Electric (IBEDC)',       'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'JED',    'name' => 'Jos Electric (JED)',            'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'BEDC',   'name' => 'Benin Electric (BEDC)',         'type' => BillTypeEnum::ELECTRICITY],
        ['serviceID' => 'KAEDCO', 'name' => 'Kaduna Electric (KAEDCO)',      'type' => BillTypeEnum::ELECTRICITY],
    ];

    public function __construct()
    {
        $config        = config('billpayment.providers.buypower');
        $this->baseUrl = $config['base_url'];
        $this->token   = $config['token'];
    }

    public function getServices(): array
    {
        return collect(self::SERVICES)->map(fn ($s) => new ProviderServiceDto(
            name:               $s['name'],
            providerServiceId:  $s['serviceID'],
            type:               $s['type'],
            provider:           BillProviderEnum::BUYPOWER,
            hasVariations:      false,     // electricity = freeform amount, no bundles
            requiresValidation: true,      // meter number must always be verified
            minAmount:          500,
            maxAmount:          null,
        ))->toArray();
    }

    // Electricity has no variation codes — user enters any amount
    public function getVariations(string $providerServiceId): array
    {
        return [];
    }

    /**
     * @throws ConnectionException
     */
    public function validateRecipient(string $providerServiceId, string $recipient): array
    {
        // BuyPower: verify meter number before purchase
        // meterType: prepaid | postpaid (passed via $recipient split or meta)
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/customer", [
                'disco'     => $providerServiceId,
                'reference' => $recipient,
                'type'      => 'prepaid', // default; pass from meta in real flow
            ]);

        return $response->json();
    }

    /**
     * @throws ConnectionException
     */
    public function purchase(BillPaymentData $dto): array
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/vend", [
                'orderId'   => $dto->reference,
                'meter'     => $dto->recipient,
                'disco'     => $dto->serviceId,
                'amount'    => $dto->amount,
                'phone'     => $dto->meta['phone'] ?? '',
                'email'     => $dto->meta['email'] ?? '',
                'vendType'  => $dto->meta['meter_type'] ?? 'prepaid',
                'force'     => false,
            ]);

        return $response->json();
    }

    public function queryStatus(string $requestId): array
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/transaction/{$requestId}");

        return $response->json();
    }

    public function verifyWebhook(Request $request): bool
    {
        // BuyPower sends a plain Bearer token in Authorization header
        $incoming = $request->bearerToken();
        return hash_equals($this->token, (string) $incoming);
    }
}
