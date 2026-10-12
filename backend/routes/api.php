<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\TiketController;
use App\Http\Controllers\Auth\ForgotPasswordController;

// ======================================================
// LOGIN (publik)
// ======================================================

Route::post('/login', [AuthController::class, 'login']);

// ======================================================
// LUPA PASSWORD (publik)
// ======================================================

Route::prefix('forgot-password')->group(function () {
    Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp'])
        ->middleware('throttle:5,10');
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
        ->middleware('throttle:10,10');
    Route::post('/reset', [ForgotPasswordController::class, 'resetPassword'])
        ->middleware('throttle:10,10');
});

// ======================================================
// TIKET NON-MEMBER
// ======================================================

Route::post('/tiket', [TiketController::class, 'create'])->name('tiket.create');
Route::get('/tiket', [TiketController::class, 'index'])->name('tiket.index');
Route::get('/tiket/{id}', [TiketController::class, 'show'])->name('tiket.show');
Route::put('/tiket/{id}/keluar', [TiketController::class, 'keluar'])->name('tiket.keluar');
Route::delete('/tiket/{id}', [TiketController::class, 'destroy'])->name('tiket.destroy');

// ======================================================
// TRANSAKSI
// ======================================================

Route::get('/transaksi', [TiketController::class, 'transaksi'])->name('transaksi.index');

// ======================================================
// MEMBER
// ======================================================

// Route statis (harus rapi & jelas, tidak bentrok dengan {id})
Route::post('/member/check', [MemberController::class, 'check'])->name('member.check');
Route::post('/member/keluar', [MemberController::class, 'keluar'])->name('member.keluar');
Route::post('/member/reset-bulanan', [MemberController::class, 'resetBulanan'])->name('member.reset.bulanan');
Route::get('/member/cari/{kode}', [MemberController::class, 'cariByKode'])->name('member.cari');

// Log masuk/keluar member (dipakai halaman laporan)
Route::get('/member-log', [MemberController::class, 'logMember'])->name('member.log');

// CRUD member
Route::get('/member', [MemberController::class, 'index'])->name('member.index');
Route::post('/member', [MemberController::class, 'store'])->name('member.store');
Route::get('/member/{id}', [MemberController::class, 'show'])->whereNumber('id')->name('member.show');
Route::put('/member/{id}', [MemberController::class, 'update'])->whereNumber('id')->name('member.update');
Route::delete('/member/{id}', [MemberController::class, 'destroy'])->whereNumber('id')->name('member.destroy');
Route::put('/member/{id}/pembayaran', [MemberController::class, 'pembayaran'])->whereNumber('id')->name('member.pembayaran');

// ======================================================
// ROUTE YANG BUTUH LOGIN
// ======================================================

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // PETUGAS
    Route::get('/petugas', [PetugasController::class, 'index'])->name('petugas.index');
    Route::post('/petugas', [PetugasController::class, 'store'])->name('petugas.store');
    Route::put('/petugas/{id}', [PetugasController::class, 'update'])->name('petugas.update');
    Route::delete('/petugas/{id}', [PetugasController::class, 'destroy'])->name('petugas.destroy');
});