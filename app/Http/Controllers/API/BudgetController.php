<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    // Lihat semua anggaran
    public function index(Request $request)
    {
        $budgets = $request->user()->budgets()
            ->with('category') // Biar kelihatan ini anggaran kategori apa
            ->latest()
            ->get();

        return response()->json($budgets);
    }

    // Buat anggaran baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            // Memaksa format tahun-bulan (Contoh: 2026-03)
            'period' => 'required|date_format:Y-m'
        ]);

        // Cek dulu, jangan sampai bulan ini udah bikin anggaran buat kategori yang sama
        $existingBudget = $request->user()->budgets()
            ->where('category_id', $validated['category_id'])
            ->where('period', $validated['period'])
            ->first();

        if ($existingBudget) {
            return response()->json(['message' => 'Anggaran untuk kategori ini di bulan tersebut sudah ada!'], 422);
        }

        $budget = $request->user()->budgets()->create($validated);

        return response()->json($budget->load('category'), 201);
    }

    // Lihat detail 1 anggaran
    public function show(Request $request, $id)
    {
        $budget = $request->user()->budgets()->with('category')->findOrFail($id);
        return response()->json($budget);
    }

    // Edit anggaran
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'category_id' => 'exists:categories,id',
            'amount' => 'numeric|min:1',
            'period' => 'date_format:Y-m'
        ]);

        $budget = $request->user()->budgets()->findOrFail($id);
        $budget->update($validated);

        return response()->json($budget->load('category'));
    }

    // Hapus anggaran
    public function destroy(Request $request, $id)
    {
        $request->user()->budgets()->findOrFail($id)->delete();
        return response()->json(['message' => 'Anggaran berhasil dihapus']);
    }
}
