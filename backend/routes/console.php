<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\PaymentController; // atau pakai Command lebih baik

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// Schedule Generate Tagihan Bulanan
// ============================================
Schedule::call(function () {
    app(PaymentController::class)->generateTagihanBulanan();
})->monthlyOn(1, '00:05'); // setiap tanggal 1 jam 00:05