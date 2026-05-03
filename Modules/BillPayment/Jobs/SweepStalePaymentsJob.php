<?php

namespace Modules\BillPayment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\BillPayment\actions\VerifyBillPaymentAction;
use Modules\BillPayment\Enums\BillStatusEnum;
use Modules\BillPayment\Models\BillPayment;

class SweepStalePaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VerifyBillPaymentAction $verify): void
    {
        // Any payment stuck in PROCESSING for more than 10 minutes
        BillPayment::where('status', BillStatusEnum::PROCESSING)
            ->where('updated_at', '<=', now()->subMinutes(10))
            ->chunkById(50, function ($payments) use ($verify) {
                foreach ($payments as $payment) {
                    if ($payment->status === BillStatusEnum::PENDING) {
                        // Job was never picked up — re-dispatch instead of verifying
                        ProcessBillPaymentJob::dispatch($payment);
                    } else {
                        $verify->handle($payment);
                    }
                }
            });
    }
}
