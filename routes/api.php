<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\FinancialAccountController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\TransferController;

/*
|--------------------------------------------------------------------------
| API Routes - Midnight Private Wealth
|--------------------------------------------------------------------------
*/

// --- 🔓 JALUR PUBLIK ---
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// --- 🔐 JALUR PRIVAT (Wajib Login) ---
Route::middleware('auth:sanctum')->group(function () {

    // User Info & Setup
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
    Route::post('/transfers', [TransferController::class, 'store']);

    // Master Data Kategori (SUDAH DIPERBAIKI)
    // Cukup panggil nama Class-nya saja, apiResource urus sisanya!
    Route::apiResource('categories', CategoryController::class);
});
