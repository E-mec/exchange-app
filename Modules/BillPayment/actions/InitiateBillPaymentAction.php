<?php

namespace Modules\BillPayment\actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BillPayment\app\Events\BillPaymentInitiated;
use Modules\BillPayment\Enums\BillStatusEnum;
use Modules\BillPayment\Jobs\ProcessBillPaymentJob;
use Modules\BillPayment\Models\BillPayment;
use Modules\BillPayment\Models\BillService;
use Modules\Wallet\actions\ReserveFundsAction;

class InitiateBillPaymentAction
{
    public function __construct(private readonly ReserveFundsAction $reserve) {}

    public function handle(array $data, int $userId): BillPayment
    {

        // ── Idempotency check — return existing payment if already initiated ──
        $existing = BillPayment::where('idempotency_key', $data['idempotency_key'])
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return $existing; // same key = same payment, no duplicate processing
        }


        $service = BillService::active()
            ->where('slug', $data['service_slug'])
            ->firstOrFail();

        // Wallet balance check + reserve happens in DB transaction
        return DB::transaction(function () use ($data, $userId, $service) {

            $reference = 'BILL-' . strtoupper(Str::random(12));

            $reserveTx = $this->reserve->execute([
                'walletId'       => $data['wallet_id'],
                'userId'         => $userId,
                'currency'       => $data['currency'],
                'amount'         => $data['amount'],
                'reference'      => $reference,
                'idempotencyKey' => 'bill:reserve:' . $data['idempotency_key'],
                'meta'           => ['bill_reference' => $reference],
            ]);

            $billPayment = BillPayment::create([
                'user_id'              => $userId,
                'wallet_id'            => $data['wallet_id'],
                'reference'            => $reference,
                'idempotency_key'      => $data['idempotency_key'],
                'type'                 => $service->type->value,
                'provider'             => $service->provider->value,
                'bill_service_id'      => $service->id,
                'service_id'           => $service->provider_service_id,
                'recipient'            => $data['recipient'],
                'variation_code'       => $data['variation_code'] ?? null,
                'amount'               => $data['amount'],
                'currency'             => $data['currency'],
                'status'               => BillStatusEnum::PENDING->value,
                'wallet_reserve_tx_id' => $reserveTx->id,
                'meta'                 => $data['meta'] ?? null,
            ]);

            ProcessBillPaymentJob::dispatch($billPayment)->afterCommit();
            event(new BillPaymentInitiated($billPayment));

            return $billPayment;
        });
    }
}
