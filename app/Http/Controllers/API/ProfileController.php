<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

// Import library Intervention Image v3
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        // CARA SULTAN: Ambil data user langsung dari request API.
        /** @var \App\Models\User $user */
        $user = $request->user();

        // 1. Validasi Input (Bebas format foto standar, maksimal 5MB)
        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // 2. Update Nama
        $user->name = $request->name;

        // 3. Logika Upload & Konversi Otomatis ke WebP
        if ($request->hasFile('avatar')) {

            // 🚨 SATPAM COOLDOWN 1 MINGGU
            if ($user->avatar_updated_at) {
                // Hitung kapan user boleh ganti foto lagi (7 hari dari terakhir ganti)
                $bisaGantiLagi = Carbon::parse($user->avatar_updated_at)->addDays(7);

                // Kalau waktu sekarang masih kurang dari jadwal boleh ganti
                if (now()->lessThan($bisaGantiLagi)) {
                    // Hitung sisa waktu biar pesannya enak dibaca
                    $sisaWaktu = now()->diff($bisaGantiLagi);
                    $sisaHari = $sisaWaktu->d;
                    $sisaJam = $sisaWaktu->h;

                    return response()->json([
                        'message' => "Sabar Bang! Foto profil baru bisa diganti lagi dalam $sisaHari hari, $sisaJam jam."
                    ], 422); // 422 Unprocessable Entity (Ditolak oleh sistem)
                }
            }

            $file = $request->file('avatar');

            // Bikin nama file baru yang unik dengan akhiran .webp
            $filename = 'avatar_' . $user->id . '_' . time() . '.webp';
            $path = 'avatars/' . $filename;

            try {
                // Inisialisasi Intervention Image
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file);

                // Potong 300x300 pixel biar rapi di Sidebar
                $image->cover(300, 300);
                $encodedImage = $image->toWebp(80);

                // 🚨 PERBAIKAN HAPUS FOTO LAMA
                // Gunakan getRawOriginal agar yang diambil teks aslinya ("avatars/nama.webp"), BUKAN URL lengkapnya
                $oldAvatarPath = $user->getRawOriginal('avatar');

                if ($oldAvatarPath && Storage::disk('public')->exists($oldAvatarPath)) {
                    Storage::disk('public')->delete($oldAvatarPath);
                }

                // Simpan foto WebP baru ke folder storage
                Storage::disk('public')->put($path, (string) $encodedImage);

                // Update path foto dan Catat Waktu Ganti Foto di database
                $user->avatar = $path;
                $user->avatar_updated_at = now();

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Gagal memproses foto: ' . $e->getMessage()
                ], 500);
            }
        }

        // 4. Simpan Perubahan ke Database
        $user->save();

        // 5. Kembalikan Response Sukses ke React
        return response()->json([
            'message' => 'Profil berhasil diperbarui!',
            // fresh() memastikan data yang dikirim adalah yang terbaru dari database
            'user' => $user->fresh(),
        ], 200);
    }
}
