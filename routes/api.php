<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\FinancialAccountController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\TransferController;
use App\Http\Controllers\API\SavingController;

/*
|--------------------------------------------------------------------------
| API Routes - Midnight Private Wealth (Bank-Grade Security)
|--------------------------------------------------------------------------
*/

// ==========================================
// 🔓 JALUR PUBLIK (Tidak Perlu Login)
// ==========================================
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// 🛡️ SECURITY PATCH: Jalur OTP dengan pembatasan akses (Throttle)
// Maksimal 5 kali percobaan tebak OTP per 1 menit (Anti Brute-Force Bot)
Route::middleware('throttle:5,1')->post('verify-otp', [AuthController::class, 'verifyOtp']);

// Maksimal 3 kali minta kirim ulang OTP per 1 menit (Anti Spam Email)
Route::middleware('throttle:3,1')->post('resend-otp', [AuthController::class, 'resendOtp']);


// ==========================================
// 🔐 JALUR PRIVAT (Wajib Login & Punya Token Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // User Info & Setup Awal
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/setup', [AuthController::class, 'setup']);

    // Dashboard & Analytics (Grafik Sultan)
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/analytics', [DashboardController::class, 'analytics']);

    // Portofolio Keuangan (Dompet/Akun)
    Route::apiResource('financial-accounts', FinancialAccountController::class);

    // Transaksi (CRUD)
    Route::apiResource('transactions', TransactionController::class);

    // Transfer Antar Portofolio
    Route::apiResource('/transfers', TransferController::class);

    // Tabungan (Savings) - Fitur Baru!
    Route::apiResource('savings', SavingController::class);

    // Master Data Kategori
    Route::apiResource('categories', CategoryController::class);

});
