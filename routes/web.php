<?php

use Illuminate\Support\Facades\Route;

// Halaman Bawaan Laravel
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| JALUR TESTING EMAIL SULTAN (Hapus kalau aplikasi sudah Rilis/Produksi)
|--------------------------------------------------------------------------
*/

// 1. Cek Tampilan Email OTP
Route::get('/test-email-otp', function () {
    // Bikin user bohongan (dummy) buat ngetes nama
    $dummyUser = new \App\Models\User();
    $dummyUser->name = 'Vincentius Ananto Galih Rinaldy';

    // Panggil file: resources/views/emails/otp.blade.php
    return view('emails.otp', [
        'otp' => '889900',
        'user' => $dummyUser
    ]);
});

// 2. Cek Tampilan Email Reset Password
Route::get('/test-email-reset', function () {
    // Bikin user bohongan
    $dummyUser = new \App\Models\User();
    $dummyUser->name = 'Vincentius Ananto Galih Rinaldy';

    // Panggil file: resources/views/emails/reset.blade.php
    return view('emails.reset', [
        'resetUrl' => 'http://localhost:5173/reset-password?token=token-sakti-123-xyz',
        'user' => $dummyUser
    ]);
});
