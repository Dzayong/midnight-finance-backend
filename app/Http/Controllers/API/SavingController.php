<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\Saving;
use App\Models\FinancialAccount;

class SavingController extends Controller
{
    public function index(Request $request)
    {
        $savings = $request->user()->savings()->with('financialAccount')->latest()->get();
        return response()->json($savings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'numeric|min:0',
            'deadline' => 'nullable|date'
        ]);

        if (!isset($validated['current_amount'])) {
            $validated['current_amount'] = 0;
        }

        $saving = $request->user()->savings()->create($validated);

        // --- LOGIKA PRD 5.1 (POTONG SALDO SAAT BIKIN TABUNGAN BARU) ---
        if ($validated['current_amount'] > 0) {
            $account = FinancialAccount::findOrFail($validated['financial_account_id']);
            $account->balance -= $validated['current_amount']; // Uang dipindah ke tabungan
            $account->save();
        }

        return response()->json($saving->load('financialAccount'), 201);
    }

    public function show(Request $request, $id)
    {
        $saving = $request->user()->savings()->with('financialAccount')->findOrFail($id);
        return response()->json($saving);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'target_amount' => 'numeric|min:1',
            'current_amount' => 'numeric|min:0',
            'deadline' => 'nullable|date'
        ]);

        $saving = $request->user()->savings()->findOrFail($id);
        $oldAmount = $saving->current_amount;

        $saving->update($validated);

        // --- LOGIKA PRD 5.1 (UPDATE SALDO KALAU NABUNG/NARIK UANG) ---
        if (isset($validated['current_amount']) && $oldAmount !== $validated['current_amount']) {
            $account = FinancialAccount::findOrFail($saving->financial_account_id);
            $selisih = $validated['current_amount'] - $oldAmount;

            // Kalau selisih positif (Nabung), saldo dompet dipotong
            // Kalau selisih negatif (Narik Tabungan), saldo dompet ditambah
            $account->balance -= $selisih;
            $account->save();
        }

        return response()->json($saving->load('financialAccount'));
    }

    public function destroy(Request $request, $id)
    {
        $saving = $request->user()->savings()->findOrFail($id);

        // --- LOGIKA BATAL NABUNG (KEMBALIKAN UANG KE DOMPET) ---
        if ($saving->current_amount > 0) {
            $account = FinancialAccount::findOrFail($saving->financial_account_id);
            $account->balance += $saving->current_amount; // Uang balik ke dompet
            $account->save();
        }

        $saving->delete();
        return response()->json(['message' => 'Target tabungan dihapus & Uang dikembalikan ke dompet']);
    }
}
