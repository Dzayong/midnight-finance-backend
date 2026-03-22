<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FinancialAccount;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // 💡 HELPER: Cek Cooldown OTP (3 Menit mutlak, kebal bug zona waktu)
    private function checkCooldown($user)
    {
        if ($user && $user->otp_code && $user->updated_at) {
            $secondsPassed = now()->diffInSeconds($user->updated_at);
            if ($secondsPassed < 180) {
                $wait = ceil((180 - $secondsPassed) / 60);
                return "Tunggu {$wait} menit lagi sebelum meminta kode verifikasi baru.";
            }
        }
        return false;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->status === 'active') {
            return response()->json(['message' => 'Alamat email sudah terdaftar. Silakan masuk ke akun Anda.'], 400);
        }

        if ($cooldownMsg = $this->checkCooldown($user)) {
            return response()->json(['message' => $cooldownMsg], 429);
        }

        $otp = rand(100000, 999999);

        $user = User::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'status' => 'inactive',
                'otp_code' => (string) $otp,
                'otp_expires_at' => now()->addMinutes(10)
            ]
        );

        Mail::send('emails.otp', ['otp' => $otp, 'user' => $user], function ($msg) use ($user) {
            $msg->to($user->email)->subject('Kode Verifikasi Keamanan - Midnight Finance');
        });

        return response()->json(['message' => 'Registrasi berhasil. Sistem sedang mengirimkan kode verifikasi ke email Anda.'], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp_code' => 'required']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Alamat email tidak ditemukan dalam sistem.'], 404);
        }

        if ($user->otp_code !== (string) $request->otp_code) {
            return response()->json(['message' => 'Kode verifikasi yang Anda masukkan tidak valid.'], 401);
        }

        if (now()->greaterThan(Carbon::parse($user->otp_expires_at))) {
            return response()->json(['message' => 'Kode verifikasi telah usang. Silakan minta kode baru.', 'expired' => true], 401);
        }

        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        return response()->json(['message' => 'Verifikasi berhasil. Silakan masuk ke brankas digital Anda.']);
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) return response()->json(['message' => 'Alamat email tidak ditemukan.'], 404);
        if ($user->status === 'active') return response()->json(['message' => 'Akun Anda sudah aktif. Silakan masuk.'], 400);

        if ($cooldownMsg = $this->checkCooldown($user)) {
            return response()->json(['message' => $cooldownMsg], 429);
        }

        $otp = rand(100000, 999999);
        $user->update([
            'otp_code' => (string) $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        Mail::send('emails.otp', ['otp' => $otp, 'user' => $user], function ($msg) use ($user) {
            $msg->to($user->email)->subject('Kode Verifikasi Baru - Midnight Finance');
        });

        return response()->json(['message' => 'Kode verifikasi baru telah dikirimkan ke email Anda.']);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();

        if (!$user) return response()->json(['message' => 'Alamat email tidak ditemukan. Silakan buat akun terlebih dahulu.'], 404);
        if (!Hash::check($request->password, $user->password)) return response()->json(['message' => 'Kata sandi yang Anda masukkan salah.'], 401);
        if ($user->otp_code) return response()->json(['message' => 'Akun belum diverifikasi. Silakan verifikasi email Anda terlebih dahulu.', 'need_otp' => true], 403);

        return response()->json([
            'message' => 'Akses diberikan. Membuka brankas digital Anda...',
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user
        ]);
    }

    public function setup(Request $request)
    {
        $user = $request->user();
        if ($user->status === 'active') return response()->json(['message' => 'Pengaturan awal telah dilakukan sebelumnya.'], 400);

        $request->validate(['accounts' => 'required|array', 'categories' => 'required|array']);

        DB::beginTransaction();
        try {
            foreach ($request->accounts as $acc) FinancialAccount::create(['user_id' => $user->id] + $acc);
            foreach ($request->categories as $cat) Category::create(['user_id' => $user->id] + $cat);

            $user->update(['status' => 'active']);
            DB::commit();
            return response()->json(['message' => 'Pengaturan berhasil. Akun Anda kini aktif.', 'user' => $user], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'Jika email terdaftar, tautan pengaturan ulang telah dikirimkan.'], 200);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['email' => $request->email, 'token' => $token, 'created_at' => now()]
        );

        $resetUrl = env('FRONTEND_URL', 'http://localhost:5173') . "/reset-password?token=$token&email=" . urlencode($request->email);
        Mail::send('emails.reset', ['resetUrl' => $resetUrl, 'user' => $user], function ($msg) use ($request) {
            $msg->to($request->email)->subject('Permintaan Atur Ulang Kata Sandi - Midnight Finance');
        });

        return response()->json(['message' => 'Tautan untuk mengatur ulang kata sandi telah dikirimkan ke email Anda.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email', 'token' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()]
        ]);

        $reset = DB::table('password_reset_tokens')->where('email', $request->email)->where('token', $request->token)->first();
        if (!$reset || now()->diffInMinutes(Carbon::parse($reset->created_at)) > 10) {
            return response()->json(['message' => 'Tautan tidak valid atau telah usang.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'Pengguna tidak ditemukan.'], 404);

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Kata sandi berhasil diubah. Seluruh sesi perangkat telah diakhiri demi keamanan.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesi Anda telah diakhiri dengan aman.']);
    }
}
