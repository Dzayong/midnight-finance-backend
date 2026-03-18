<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Tampilkan semua kategori milik user yang login.
     */
    public function index(Request $request)
    {
        // Mengurutkan berdasarkan tipe dan nama agar rapi di dropdown React
        $categories = $request->user()->categories()
            ->orderBy('type', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Simpan kategori baru dengan validasi unik per user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|in:income,expense'
        ]);

        // Logika Pencegahan Duplikat:
        // Cek apakah user sudah punya kategori dengan nama & tipe yang sama
        $exists = $request->user()->categories()
            ->where('name', $validated['name'])
            ->where('type', $validated['type'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kategori "' . $validated['name'] . '" sudah ada di portofolio Anda!'
            ], 422);
        }

        $category = $request->user()->categories()->create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ], 201);
    }

    /**
     * Detail kategori.
     */
    public function show(Request $request, $id)
    {
        $category = $request->user()->categories()->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * Update data kategori.
     */
    public function update(Request $request, $id)
    {
        $category = $request->user()->categories()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:50',
            'type' => 'in:income,expense'
        ]);

        // Cek duplikat jika nama diubah
        if (isset($validated['name']) && $validated['name'] !== $category->name) {
            $exists = $request->user()->categories()
                ->where('name', $validated['name'])
                ->where('type', $validated['type'] ?? $category->type)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Nama kategori sudah digunakan!'], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * Hapus kategori.
     */
    public function destroy(Request $request, $id)
    {
        $category = $request->user()->categories()->findOrFail($id);

        // Opsional: Cek apakah kategori sedang dipakai di transaksi sebelum hapus
        // if ($category->transactions()->exists()) {
        //     return response()->json(['message' => 'Kategori tidak bisa dihapus karena masih memiliki riwayat transaksi!'], 422);
        // }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
