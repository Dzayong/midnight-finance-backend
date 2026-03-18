<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAccount;    
// use App\Models\Debt;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $debts = $request->user()->debts()->with('financialAccount')->latest()->get();
        return response()->json($debts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:payable,receivable',
            'status' => 'in:unpaid,paid',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string'
        ]);

        if (!isset($validated['status'])) {
            $validated['status'] = 'unpaid';
        }

        $debt = $request->user()->debts()->create($validated);

        // --- LOGIKA SESUAI PRD POIN 6.1 & 6.2 ---
        $account = FinancialAccount::findOrFail($validated['financial_account_id']);
        if ($validated['status'] === 'unpaid') {
            if ($validated['type'] === 'payable') {
                $account->balance += $validated['amount']; // Ngutang: Saldo dompet kita Nambah
            } else {
                $account->balance -= $validated['amount']; // Minjemin: Saldo dompet kita Berkurang
            }
            $account->save();
        }

        return response()->json($debt->load('financialAccount'), 201);
    }

    public function show(Request $request, $id)
    {
        $debt = $request->user()->debts()->with('financialAccount')->findOrFail($id);
        return response()->json($debt);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:unpaid,paid', // Fokus utama update adalah Pelunasan
        ]);

        $debt = $request->user()->debts()->findOrFail($id);
        $oldStatus = $debt->status;

        $debt->update($validated);

        // --- LOGIKA SESUAI PRD POIN 6.3 (BAYAR LUNAS) ---
        if ($oldStatus === 'unpaid' && $debt->status === 'paid') {
            $account = FinancialAccount::findOrFail($debt->financial_account_id);

            if ($debt->type === 'payable') {
                $account->balance -= $debt->amount; // Bayar Hutang: Uang di dompet berkurang
            } else {
                $account->balance += $debt->amount; // Terima Piutang: Uang di dompet nambah
            }
            $account->save();
        }

        return response()->json($debt->load('financialAccount'));
    }

    public function destroy(Request $request, $id)
    {
        $debt = $request->user()->debts()->findOrFail($id);

        // --- LOGIKA BATALKAN TRANSAKSI ---
        if ($debt->status === 'unpaid') {
            $account = FinancialAccount::findOrFail($debt->financial_account_id);
            if ($debt->type === 'payable') {
                $account->balance -= $debt->amount; // Batal Ngutang: Duit ditarik lagi
            } else {
                $account->balance += $debt->amount; // Batal Minjemin: Duit balik ke dompet
            }
            $account->save();
        }

        $debt->delete();
        return response()->json(['message' => 'Catatan berhasil dihapus & Saldo disesuaikan']);
    }
}
