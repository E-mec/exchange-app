<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Modules\BillPayment\app\Console\SyncBillServicesCommand;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SyncBillServicesCommand::class, ['--provider=all'])
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(fn() => Log::error('bills:sync failed'));
