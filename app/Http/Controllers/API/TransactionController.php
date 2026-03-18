<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // 1. TAMPILKAN DATA (DENGAN FILTER SULTAN)
    public function index(Request $request)
    {
        $query = $request->user()->transactions()->with(['category:id,name', 'financialAccount:id,name']);

        // Filter Rentang Tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filter Dompet, Kategori, & Tipe
        if ($request->financial_account_id) $query->where('financial_account_id', $request->financial_account_id);
        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->type) $query->where('type', $request->type);

        // Sortir (Bawaan: Terbaru)
        $query->orderBy($request->sort_by ?? 'date', $request->sort_order ?? 'desc');

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    // 2. SIMPAN TRANSAKSI BARU (POTONG/TAMBAH SALDO)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'          => 'required|exists:categories,id',
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'amount'               => 'required|numeric|min:1',
            'type'                 => 'required|in:income,expense',
            'date'                 => 'required|date',
            'description'          => 'nullable|string|max:255'
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $transaction = $request->user()->transactions()->create($validated);
            $account = FinancialAccount::lockForUpdate()->findOrFail($validated['financial_account_id']);

            $validated['type'] === 'expense' ? $account->balance -= $validated['amount'] : $account->balance += $validated['amount'];
            $account->save();

            return response()->json($transaction->load(['category', 'financialAccount']), 201);
        });
    }

    // 3. UPDATE TRANSAKSI (ADJUST SALDO OTOMATIS)
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'category_id'          => 'required|exists:categories,id',
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'amount'               => 'required|numeric|min:1',
            'type'                 => 'required|in:income,expense',
            'date'                 => 'required|date',
            'description'          => 'nullable|string|max:255'
        ]);

        return DB::transaction(function () use ($validated, $request, $id) {
            $transaction = $request->user()->transactions()->findOrFail($id);

            // --- A. KEMBALIKAN SALDO LAMA ---
            $oldAccount = FinancialAccount::lockForUpdate()->findOrFail($transaction->financial_account_id);
            $transaction->type === 'expense' ? $oldAccount->balance += $transaction->amount : $oldAccount->balance -= $transaction->amount;
            $oldAccount->save();

            // --- B. UPDATE DATA TRANSAKSI ---
            $transaction->update($validated);

            // --- C. POTONG SALDO BARU (Mungkin dompetnya diganti saat edit) ---
            $newAccount = FinancialAccount::lockForUpdate()->findOrFail($validated['financial_account_id']);
            $validated['type'] === 'expense' ? $newAccount->balance -= $validated['amount'] : $newAccount->balance += $validated['amount'];
            $newAccount->save();

            return response()->json($transaction->load(['category', 'financialAccount']));
        });
    }

    // 4. HAPUS TRANSAKSI (SALDO BALIK)
    public function destroy(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $transaction = $request->user()->transactions()->findOrFail($id);
            $account = FinancialAccount::lockForUpdate()->findOrFail($transaction->financial_account_id);

            // Batalkan efek saldo
            $transaction->type === 'expense' ? $account->balance += $transaction->amount : $account->balance -= $transaction->amount;
            $account->save();
            $transaction->delete();

            return response()->json(['message' => 'Transaksi dihapus & Saldo disesuaikan.']);
        });
    }
}
