<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
    // 1. TAMPILKAN DAFTAR TRANSAKSI (LENGKAP DENGAN FILTER DARI FRONTEND)
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Transaction::with(['category', 'financialAccount'])->where('user_id', $user->id);

        // Filter berdasarkan tanggal
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        // Filter berdasarkan Dompet
        if ($request->filled('financial_account_id')) {
            $query->where('financial_account_id', $request->financial_account_id);
        }
        // Filter berdasarkan Kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        // Filter berdasarkan Tipe (Income/Expense/Transfer)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Sorting (Terbaru, Terbesar, Terkecil)
        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->get();

        return response()->json(['data' => $transactions], 200);
    }

    // 2. CATAT TRANSAKSI BARU & POTONG/TAMBAH SALDO
    public function store(Request $request)
    {
        $validated = $request->validate([
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();

        DB::beginTransaction();
        try {
            $account = FinancialAccount::where('id', $validated['financial_account_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Sesuaikan Saldo
            if ($validated['type'] === 'income') {
                $account->balance += $validated['amount'];
            } else {
                $account->balance -= $validated['amount'];
            }
            $account->save();

            // Catat Histori
            $transaction = $user->transactions()->create($validated);

            DB::commit();
            return response()->json($transaction->load(['category', 'financialAccount']), 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mencatat transaksi: ' . $e->getMessage()], 500);
        }
    }

    // 3. EDIT TRANSAKSI (KEMBALIKAN SALDO LAMA, TERAPKAN SALDO BARU)
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();

        DB::beginTransaction();
        try {
            $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->firstOrFail();
            $oldAccount = FinancialAccount::findOrFail($transaction->financial_account_id);

            // FASE 1: REVERT (KEMBALIKAN SALDO LAMA)
            if ($transaction->type === 'income') {
                $oldAccount->balance -= $transaction->amount; // Batalin masuk
            } else {
                $oldAccount->balance += $transaction->amount; // Batalin keluar
            }
            $oldAccount->save();

            // FASE 2: APPLY (TERAPKAN KE SALDO BARU)
            $newAccount = FinancialAccount::findOrFail($validated['financial_account_id']);
            if ($validated['type'] === 'income') {
                $newAccount->balance += $validated['amount'];
            } else {
                $newAccount->balance -= $validated['amount'];
            }
            $newAccount->save();

            // FASE 3: UPDATE HISTORI
            $transaction->update($validated);

            DB::commit();
            return response()->json($transaction->load(['category', 'financialAccount']), 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengubah transaksi: ' . $e->getMessage()], 500);
        }
    }

    // 4. HAPUS TRANSAKSI & KEMBALIKAN SALDO
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        DB::beginTransaction();
        try {
            $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->firstOrFail();
            $account = FinancialAccount::findOrFail($transaction->financial_account_id);

            // REVERT SALDO
            if ($transaction->type === 'income') {
                $account->balance -= $transaction->amount; // Batalin masuk
            } else {
                $account->balance += $transaction->amount; // Batalin keluar
            }
            $account->save();

            $transaction->delete();

            DB::commit();
            return response()->json(['message' => 'Transaksi berhasil dihapus dan saldo dikembalikan'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus transaksi: ' . $e->getMessage()], 500);
        }
    }
}
