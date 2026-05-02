<?php

namespace Modules\BillPayment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\BillPayment\actions\ProcessBillPaymentAction;
use Modules\BillPayment\Models\BillPayment;
use Throwable;

class ProcessBillPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $backoff = 10; // seconds between retries

    public function __construct(public readonly BillPayment $billPayment) {}

    /**
     * @throws Throwable
     */
    public function handle(ProcessBillPaymentAction $action): void
    {
        $action->handle($this->billPayment);
    }
}
