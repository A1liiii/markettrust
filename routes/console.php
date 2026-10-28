<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler pemantauan batas waktu pembayaran transaksi (UC-13C)
Schedule::command('transactions:check-payment-deadlines')->everyMinute();

// Scheduler pemantauan batas waktu penyerahan transaksi (UC-14B)
Schedule::command('transactions:check-delivery-deadlines')->everyMinute();
