<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAccount;

class FinancialAccountController extends Controller
{
    // Tampilkan semua dompet milik user login
    public function index(Request $request)
    {
        $accounts = $request->user()->financialAccounts()->orderBy('name', 'asc')->get();
        return response()->json(['status' => 'success', 'data' => $accounts]);
    }

    // Tambah dompet baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,ewallet,investment',
            'balance' => 'required|numeric|min:0',
        ]);

        $account = $request->user()->financialAccounts()->create($validated);
        return response()->json(['status' => 'success', 'data' => $account], 201);
    }

    // Update Nama/Tipe Dompet (Biasanya saldo gak diedit di sini, tapi lewat transaksi)
    public function update(Request $request, $id)
    {
        $account = $request->user()->financialAccounts()->findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,ewallet,investment',
        ]);

        $account->update($validated);
        return response()->json(['status' => 'success', 'data' => $account]);
    }

    // Hapus Dompet
    public function destroy(Request $request, $id)
    {
        $account = $request->user()->financialAccounts()->findOrFail($id);
        $account->delete();
        return response()->json(['message' => 'Dompet berhasil dihapus']);
    }
}
