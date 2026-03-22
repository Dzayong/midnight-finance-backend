<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAccount;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:financial_accounts,id',
            'to_account_id' => 'required|exists:financial_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:1',
            'admin_fee' => 'nullable|numeric|min:0',
            'date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();
        $adminFee = $validated['admin_fee'] ?? 0;
        $totalDeduction = $validated['amount'] + $adminFee;

        $fromAccount = FinancialAccount::where('id', $validated['from_account_id'])->where('user_id', $user->id)->firstOrFail();
        $toAccount = FinancialAccount::where('id', $validated['to_account_id'])->where('user_id', $user->id)->firstOrFail();

        if ($fromAccount->balance < $totalDeduction) {
            return response()->json(['message' => 'Saldo dompet asal tidak mencukupi untuk nominal transfer beserta biaya admin!'], 400);
        }

        $transferCategory = Category::where('user_id', $user->id)->where('name', 'Transfer Internal')->first();
        if (!$transferCategory) {
            $transferCategory = new Category();
            $transferCategory->user_id = $user->id;
            $transferCategory->name = 'Transfer Internal';
            $transferCategory->type = 'expense';
            $transferCategory->save();
        }

        $adminCategory = null;
        if ($adminFee > 0) {
            $adminCategory = Category::where('user_id', $user->id)->where('name', 'Biaya Admin Bank')->first();
            if (!$adminCategory) {
                $adminCategory = new Category();
                $adminCategory->user_id = $user->id;
                $adminCategory->name = 'Biaya Admin Bank';
                $adminCategory->type = 'expense';
                $adminCategory->save();
            }
        }

        DB::beginTransaction();
        try {
            $fromAccount->balance -= $totalDeduction;
            $fromAccount->save();

            $toAccount->balance += $validated['amount'];
            $toAccount->save();

            $now = now();

            $user->transactions()->create([
                'category_id' => $transferCategory->id,
                'financial_account_id' => $fromAccount->id,
                'amount' => $validated['amount'],
                'type' => 'transfer',
                'date' => $validated['date'],
                'description' => 'Transfer Keluar ke ' . $toAccount->name . ($validated['description'] ? ' - ' . $validated['description'] : ''),
                'created_at' => $now,
                'updated_at' => $now
            ]);

            $user->transactions()->create([
                'category_id' => $transferCategory->id,
                'financial_account_id' => $toAccount->id,
                'amount' => $validated['amount'],
                'type' => 'transfer',
                'date' => $validated['date'],
                'description' => 'Transfer Masuk dari ' . $fromAccount->name . ($validated['description'] ? ' - ' . $validated['description'] : ''),
                'created_at' => $now,
                'updated_at' => $now
            ]);

            if ($adminFee > 0) {
                $user->transactions()->create([
                    'category_id' => $adminCategory->id,
                    'financial_account_id' => $fromAccount->id,
                    'amount' => $adminFee,
                    'type' => 'expense',
                    'date' => $validated['date'],
                    'description' => 'Biaya admin transfer ke ' . $toAccount->name,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Transfer berhasil! ' . ($adminFee > 0 ? "Biaya admin Rp " . number_format($adminFee, 0, ',', '.') . " dicatat." : "Bebas biaya admin.")], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Transfer gagal diproses. Error: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();

        // Cari transaksi yang diklik dan saudaranya
        $baseTransaction = Transaction::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $siblings = Transaction::where('user_id', $user->id)->where('created_at', $baseTransaction->created_at)->get();

        // 💡 AMBIL DOMPET ASAL & TUJUAN LANGSUNG DARI DATABASE (Anti-terbalik)
        $sourceTrx = $siblings->first(fn($t) => str_contains(strtoupper($t->description), 'KELUAR'));
        $targetTrx = $siblings->first(fn($t) => str_contains(strtoupper($t->description), 'MASUK'));

        if (!$sourceTrx || !$targetTrx) {
            return response()->json(['message' => 'Data transfer korup atau tidak valid.'], 400);
        }

        $fromAccount = FinancialAccount::where('id', $sourceTrx->financial_account_id)->firstOrFail();
        $toAccount = FinancialAccount::where('id', $targetTrx->financial_account_id)->firstOrFail();

        // Cek Admin Fee lama
        $adminTrx = $siblings->first(fn($t) => $t->type === 'expense' && str_contains(strtoupper($t->description), 'BIAYA ADMIN'));
        $adminFee = $adminTrx ? $adminTrx->amount : 0;
        $totalDeduction = $validated['amount'] + $adminFee;

        DB::beginTransaction();
        try {
            // FASE 1: REVERT (KEMBALIKAN SALDO DENGAN UBAH KE UPPERCASE BIAR AMAN)
            foreach ($siblings as $trx) {
                $acc = FinancialAccount::find($trx->financial_account_id);
                if ($acc) {
                    $descUpper = strtoupper($trx->description); // 👈 OBAT ANTI BUG KELUAR vs KELUAR
                    if ($trx->type === 'transfer' && str_contains($descUpper, 'KELUAR')) {
                        $acc->balance += $trx->amount;
                    } elseif ($trx->type === 'transfer' && str_contains($descUpper, 'MASUK')) {
                        $acc->balance -= $trx->amount;
                    } elseif ($trx->type === 'expense' && str_contains($descUpper, 'BIAYA ADMIN')) {
                        $acc->balance += $trx->amount;
                    }
                    $acc->save();
                }
                $trx->delete();
            }

            $fromAccount->refresh();
            $toAccount->refresh();

            // FASE 2: CEK SALDO
            // Sekarang Saldo Cash sudah = 84.000, pasti Lolos untuk bayar 65.000!
            if ($fromAccount->balance < $totalDeduction) {
                DB::rollBack();
                return response()->json(['message' => 'Saldo dompet asal tidak mencukupi untuk update nominal transfer baru!'], 400);
            }

            // FASE 3: RE-CREATE DENGAN DATA BARU
            $transferCategory = Category::where('user_id', $user->id)->where('name', 'Transfer Internal')->first();
            $adminCategory = $adminFee > 0 ? Category::where('user_id', $user->id)->where('name', 'Biaya Admin Bank')->first() : null;

            $fromAccount->balance -= $totalDeduction;
            $fromAccount->save();

            $toAccount->balance += $validated['amount'];
            $toAccount->save();

            $now = now();
            $descInput = $validated['description'] ?? '';

            $user->transactions()->create([
                'category_id' => $transferCategory->id,
                'financial_account_id' => $fromAccount->id,
                'amount' => $validated['amount'],
                'type' => 'transfer',
                'date' => $validated['date'],
                'description' => 'Transfer Keluar ke ' . $toAccount->name . ($descInput ? ' - ' . $descInput : ''),
                'created_at' => $now,
                'updated_at' => $now
            ]);

            $user->transactions()->create([
                'category_id' => $transferCategory->id,
                'financial_account_id' => $toAccount->id,
                'amount' => $validated['amount'],
                'type' => 'transfer',
                'date' => $validated['date'],
                'description' => 'Transfer Masuk dari ' . $fromAccount->name . ($descInput ? ' - ' . $descInput : ''),
                'created_at' => $now,
                'updated_at' => $now
            ]);

            if ($adminFee > 0 && $adminCategory) {
                $user->transactions()->create([
                    'category_id' => $adminCategory->id,
                    'financial_account_id' => $fromAccount->id,
                    'amount' => $adminFee,
                    'type' => 'expense',
                    'date' => $validated['date'],
                    'description' => 'Biaya admin transfer ke ' . $toAccount->name,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Transfer berhasil direvisi dengan akurat!'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengubah transfer. Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $baseTransaction = Transaction::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $siblings = Transaction::where('user_id', $user->id)->where('created_at', $baseTransaction->created_at)->get();

        DB::beginTransaction();
        try {
            foreach ($siblings as $trx) {
                $acc = FinancialAccount::find($trx->financial_account_id);
                if ($acc) {
                    $descUpper = strtoupper($trx->description); // 👈 OBAT ANTI BUG KELUAR vs KELUAR
                    if ($trx->type === 'transfer' && str_contains($descUpper, 'KELUAR')) {
                        $acc->balance += $trx->amount;
                    } elseif ($trx->type === 'transfer' && str_contains($descUpper, 'MASUK')) {
                        $acc->balance -= $trx->amount;
                    } elseif ($trx->type === 'expense' && str_contains($descUpper, 'BIAYA ADMIN')) {
                        $acc->balance += $trx->amount;
                    }
                    $acc->save();
                }
                $trx->delete();
            }
            DB::commit();
            return response()->json(['message' => 'Transfer berhasil dibatalkan dan saldo dikembalikan!'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus transfer. Error: ' . $e->getMessage()], 500);
        }
    }
}
