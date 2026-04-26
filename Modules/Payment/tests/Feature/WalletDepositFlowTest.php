<?php

use App\Enums\StatusEnum;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Payment\actions\InitializePayment;
use Modules\Payment\app\Gateways\PaystackGateway;
use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\WebhookService;
use Modules\Wallet\Models\PaymentIntent;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    app()->instance(PaystackGateway::class, new class implements PaymentGatewayInterface {
        public function initialize(array $data): array
        {
            return [
                'checkout_url' => 'https://checkout.test/' . $data['reference'],
                'provider_reference' => $data['reference'] . '-provider',
                'meta' => [
                    'checkout_url' => 'https://checkout.test/' . $data['reference'],
                ],
            ];
        }

        public function verify(string $reference): array
        {
            return [];
        }
    });
});

test('wallet deposit returns a checkout url and is idempotent', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'api');

    $payload = [
        'amount' => 5000,
        'currency' => 'NGN',
        'deposit_channel' => 'paystack',
        'idempotency_key' => (string) Str::uuid(),
    ];

    $firstResponse = $this->postJson('/api/user/initiate/deposit', $payload);
    $secondResponse = $this->postJson('/api/user/initiate/deposit', $payload);

    $firstResponse->assertOk();
    expect($firstResponse->json('data.checkout_url'))->toStartWith('https://checkout.test/');
    expect($firstResponse->json('data.status'))->toBe(StatusEnum::PROCESSING->value);

    $secondResponse->assertOk()
        ->assertJsonPath('data.reference', $firstResponse->json('data.reference'))
        ->assertJsonPath('data.checkout_url', $firstResponse->json('data.checkout_url'))
        ->assertJsonPath('data.status', StatusEnum::PROCESSING->value);

    expect(PaymentIntent::count())->toBe(1)
        ->and(Payment::count())->toBe(1);
});

test('reused pending payments must match the original payload', function () {
    $user = User::factory()->create();

    Payment::create([
        'user_id' => $user->id,
        'reference' => 'DEP-MISMATCH',
        'provider' => 'paystack',
        'amount' => '5000.00',
        'currency' => 'NGN',
        'status' => PaymentStatusEnum::PENDING->value,
        'provider_reference' => 'DEP-MISMATCH-provider',
        'meta' => [
            'checkout_url' => 'https://checkout.test/DEP-MISMATCH',
        ],
    ]);

    expect(fn () => app(InitializePayment::class)->execute([
        'reference' => 'DEP-MISMATCH',
        'provider' => 'paystack',
        'amount' => 7500,
        'currency' => 'NGN',
        'email' => $user->email,
    ], $user->id))->toThrow(\InvalidArgumentException::class, 'Existing payment does not match requested payload.');
});

test('wallet deposit rejects unsupported deposit channels', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'api');

    $response = $this->postJson('/api/user/initiate/deposit', [
        'amount' => 5000,
        'currency' => 'NGN',
        'deposit_channel' => 'bank',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['deposit_channel']);
});

test('successful webhook credits wallet and syncs deposit intent once', function () {
    $user = User::factory()->create();

    Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'NGN',
        'available_balance' => '0',
        'reserved_balance' => '0',
        'ledger_balance' => '0',
    ]);

    $this->actingAs($user, 'api');

    $payload = [
        'amount' => 5000,
        'currency' => 'NGN',
        'deposit_channel' => 'paystack',
        'idempotency_key' => (string) Str::uuid(),
    ];

    $response = $this->postJson('/api/user/initiate/deposit', $payload);
    $response->assertOk();

    $reference = $response->json('data.reference');
    $payment = Payment::query()->where('reference', $reference)->firstOrFail();

    app(WebhookService::class)->webhook([
        'data' => [
            'reference' => $payment->provider_reference,
            'status' => 'success',
        ],
    ], 'paystack');

    $wallet = Wallet::query()
        ->where('user_id', $user->id)
        ->where('currency', 'NGN')
        ->firstOrFail();

    $intent = PaymentIntent::query()->where('reference', $reference)->firstOrFail();

    expect((float) $wallet->refresh()->available_balance)->toEqual(5000.0)
        ->and($intent->refresh()->status)->toBe(StatusEnum::SUCCESS)
        ->and($intent->checkout_url)->toBeNull();

    app(WebhookService::class)->webhook([
        'data' => [
            'reference' => $payment->provider_reference,
            'status' => 'success',
        ],
    ], 'paystack');

    expect((float) $wallet->refresh()->available_balance)->toEqual(5000.0);
});
