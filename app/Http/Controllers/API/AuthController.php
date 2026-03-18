<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialAccount; // Sesuaikan jika nama model Abang beda
use App\Models\Category;         // Sesuaikan jika nama model Abang beda

class AuthController extends Controller
{
    // 1. REGISTRASI & KIRIM OTP
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $otp = rand(100000, 999999);

        // Status awal: INACTIVE
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'inactive',
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10)
        ]);

        Mail::raw("Halo {$user->name}! Kode OTP Anda adalah: {$otp}. Kode ini akan hangus dalam 10 menit.", function ($message) use ($user) {
            $message->to($user->email)->subject('Kode Verifikasi Midnight Finance');
        });

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan cek email/log untuk kode OTP.',
            'email' => $user->email
        ], 201);
    }

    // 2. VERIFIKASI OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'Kode OTP salah atau email tidak ditemukan.'], 401);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Kode OTP sudah kadaluarsa. Silakan request ulang.'], 401);
        }

        // Hapus OTP, tapi biarkan status INACTIVE (agar masuk halaman Setup)
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        return response()->json(['message' => 'Akun berhasil diverifikasi! Silakan login.']);
    }

    // 3. LOGIN (Mendukung Multi-Device)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau Password salah'], 401);
        }

        // Tolak kalau belum OTP
        if ($user->otp_code !== null) {
            return response()->json(['message' => 'Akun belum diverifikasi. Silakan masukkan kode OTP terlebih dahulu.'], 403);
        }

        // Bikin token baru tanpa menghapus token lama (Bisa login di HP & Laptop bersamaan)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    // 4. SETUP BRANKAS AWAL (Ubah status jadi Active)
    // 4. SETUP BRANKAS AWAL (Simpan Data + Ubah status jadi Active)
    public function setup(Request $request)
    {
        $request->validate([
            'accounts' => 'required|array',
            'categories' => 'required|array',
        ]);

        $user = $request->user();

        // Pakai Transaksi Database biar aman (Kalau error 1, batal semua)
        DB::beginTransaction();

        try {
            // 1. Looping dan Simpan Data Dompet (Accounts)
            foreach ($request->accounts as $acc) {
                FinancialAccount::create([
                    'user_id' => $user->id,
                    'name'    => $acc['name'],
                    'type'    => $acc['type'],
                    'balance' => $acc['balance'],
                ]);
            }

            // 2. Looping dan Simpan Data Kategori
            foreach ($request->categories as $cat) {
                Category::create([
                    'user_id' => $user->id,
                    'name'    => $cat['name'],
                    'type'    => $cat['type'],
                ]);
            }

            // 3. Ubah status user agar tidak terjebak di halaman Setup lagi
            $user->status = 'active';
            $user->save();

            // Selesai! Simpan semua perubahan ke database secara permanen
            DB::commit();

            return response()->json([
                'message' => 'Setup berhasil! Data brankas telah disimpan dan akun sudah aktif.',
                'user'    => $user
            ], 200);

        } catch (\Exception $e) {
            // Kalau ada error (misal nama kolom salah), batalkan semua dan kasih tau errornya
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan data Setup: ' . $e->getMessage()
            ], 500);
        }
    }

    // 5. LUPA PASSWORD (Kirim Link)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Jika email terdaftar, link reset telah dikirim!'], 200);
        }

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['email' => $request->email, 'token' => $token, 'created_at' => Carbon::now()]
        );

        $resetUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        Mail::send([], [], function ($message) use ($request, $resetUrl) {
            $message->to($request->email)
                ->subject('Reset Kata Sandi - Midnight Finance')
                ->html("<h2>Halo Sultan!</h2><p>Klik link ini untuk sandi baru: <a href='{$resetUrl}'>Atur Ulang Sandi</a></p>");
        });

        return response()->json(['message' => 'Link atur ulang kata sandi telah dikirim ke email Anda!'], 200);
    }

    // 6. GANTI PASSWORD + GLOBAL KILL SWITCH
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetRequest || now()->diffInMinutes(\Carbon\Carbon::parse($resetRequest->created_at)) > 10) {
            return response()->json(['message' => 'Token tidak valid atau sudah kadaluarsa.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'Pengguna tidak ditemukan!'], 404);

        $user->password = Hash::make($request->password);
        $user->save();

        // 🚨 GLOBAL KILL SWITCH: Hapus semua token di semua perangkat!
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Kata sandi diubah! Semua perangkat telah dilogout.'], 200);
    }

    // 7. LOGOUT (Satu Perangkat)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }
}
