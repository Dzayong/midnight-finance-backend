<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;
use Exception;

/**
     * Transfer Antar Dompet
     *
     * Endpoint ini memindahkan saldo dari satu dompet ke dompet lain milik user yang sama, beserta pencatatan biaya admin (jika ada).
     *
     * @authenticated
     * @bodyParam from_account_id int required ID Dompet asal pemotong saldo. Example: 1
     * @bodyParam to_account_id int required ID Dompet tujuan penerima saldo. Example: 2
     * @bodyParam amount numeric required Nominal yang ditransfer. Example: 50000
     * @bodyParam admin_fee numeric Biaya admin (opsional). Example: 2500
     * @bodyParam admin_fee_category_id int ID Kategori untuk biaya admin (wajib jika admin_fee diisi). Example: 3
     * @bodyParam date date required Tanggal transfer. Example: 2026-03-11
     * @bodyParam description string Catatan transfer. Example: Pindah dana darurat ke GoPay
     * * @response 200 {
     * "message": "Transfer berhasil diproses!"
     * }
     */

class TransferController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'from_account_id' => 'required|exists:financial_accounts,id',
            'to_account_id' => 'required|exists:financial_accounts,id|different:from_account_id', // Nggak boleh transfer ke dompet yang sama
            'amount' => 'required|numeric|min:1',
            'admin_fee' => 'nullable|numeric|min:0', // Biaya admin boleh kosong/0
            'admin_fee_category_id' => 'required_with:admin_fee|exists:categories,id', // Wajib ada kategori kalau ada biaya admin
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();

        // Cari dompet asal dan tujuan milik user ini
        $fromAccount = FinancialAccount::where('id', $validated['from_account_id'])->where('user_id', $user->id)->firstOrFail();
        $toAccount = FinancialAccount::where('id', $validated['to_account_id'])->where('user_id', $user->id)->firstOrFail();

        $adminFee = $validated['admin_fee'] ?? 0;
        $totalDeduction = $validated['amount'] + $adminFee;

        // Cek apakah saldo cukup untuk transfer + bayar admin
        if ($fromAccount->balance < $totalDeduction) {
            return response()->json(['message' => 'Saldo dompet asal tidak mencukupi untuk transfer dan biaya admin!'], 400);
        }

        // 2. MULAI MODE AMAN (Database Transaction)
        DB::beginTransaction();
        try {
            // A. Tarik uang dari dompet asal
            $fromAccount->balance -= $totalDeduction;
            $fromAccount->save();

            // B. Masukkan uang ke dompet tujuan
            $toAccount->balance += $validated['amount'];
            $toAccount->save();

            // C. Catat Biaya Admin sebagai "Pengeluaran" (Jika ada)
            if ($adminFee > 0) {
                $user->transactions()->create([
                    'category_id' => $validated['admin_fee_category_id'],
                    'financial_account_id' => $fromAccount->id,
                    'amount' => $adminFee,
                    'type' => 'expense',
                    'date' => $validated['date'],
                    'description' => $validated['description'] ? 'Admin Transfer: ' . $validated['description'] : 'Biaya Admin Transfer'
                ]);
            }

            DB::commit(); // Simpan permanen!

            return response()->json([
                'message' => 'Transfer berhasil diproses!',
                'data' => [
                    'from_account_new_balance' => $fromAccount->balance,
                    'to_account_new_balance' => $toAccount->balance,
                    'transferred_amount' => $validated['amount'],
                    'admin_fee_recorded' => $adminFee
                ]
            ], 200);

        } catch (Exception $e) {
            DB::rollBack(); // Batal jika error
            return response()->json(['message' => 'Transfer gagal diproses. Error: ' . $e->getMessage()], 500);
        }
    }
}
