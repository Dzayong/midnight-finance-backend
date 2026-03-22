<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAccount;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Exception;

class SavingController extends Controller
{
    private function getSavingCategory($userId, $type)
    {
        $name = $type === 'expense' ? 'Alokasi Tabungan' : 'Pencairan Tabungan';
        $category = Category::where('user_id', $userId)->where('name', $name)->where('type', $type)->first();

        if (!$category) {
            $category = new Category();
            $category->user_id = $userId;
            $category->name = $name;
            $category->type = $type;
            $category->save();
        }
        return $category;
    }

    public function index(Request $request) {
        return response()->json($request->user()->savings()->with('financialAccount')->latest()->get());
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'numeric|min:0',
            'deadline' => 'nullable|date'
        ]);

        if (!isset($validated['current_amount'])) $validated['current_amount'] = 0;
        $user = $request->user();

        DB::beginTransaction();
        try {
            $saving = $user->savings()->create($validated);
            if ($validated['current_amount'] > 0) {
                $account = FinancialAccount::findOrFail($validated['financial_account_id']);
                $account->balance -= $validated['current_amount'];
                $account->save();

                $category = $this->getSavingCategory($user->id, 'expense');
                $user->transactions()->create([
                    'category_id' => $category->id,
                    'financial_account_id' => $account->id,
                    'amount' => $validated['current_amount'],
                    'type' => 'expense',
                    'date' => now()->toDateString(),
                    'description' => 'Nabung untuk: ' . $saving->name
                ]);
            }
            DB::commit();
            return response()->json($saving->load('financialAccount'), 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan tabungan'], 500);
        }
    }

    public function show(Request $request, $id) {
        return response()->json($request->user()->savings()->with('financialAccount')->findOrFail($id));
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'target_amount' => 'numeric|min:1',
            'current_amount' => 'numeric|min:0',
            'deadline' => 'nullable|date'
        ]);

        $user = $request->user();
        DB::beginTransaction();
        try {
            $saving = $user->savings()->findOrFail($id);
            $oldAmount = $saving->current_amount;
            $saving->update($validated);

            if (isset($validated['current_amount']) && $oldAmount !== $validated['current_amount']) {
                $account = FinancialAccount::findOrFail($saving->financial_account_id);
                $selisih = $validated['current_amount'] - $oldAmount;

                if ($selisih > 0) {
                    $account->balance -= $selisih;
                    $category = $this->getSavingCategory($user->id, 'expense');
                    $type = 'expense';
                    $desc = 'Top up tabungan: ' . $saving->name;
                } else {
                    $account->balance += abs($selisih);
                    $category = $this->getSavingCategory($user->id, 'income');
                    $type = 'income';
                    $desc = 'Tarik dana tabungan: ' . $saving->name;
                }
                $account->save();

                $user->transactions()->create([
                    'category_id' => $category->id,
                    'financial_account_id' => $account->id,
                    'amount' => abs($selisih),
                    'type' => $type,
                    'date' => now()->toDateString(),
                    'description' => $desc
                ]);
            }
            DB::commit();
            return response()->json($saving->load('financialAccount'));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengubah tabungan'], 500);
        }
    }

    // 💡 PERUBAHAN DEWA DI SINI (Tangkap Status dari Frontend)
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $status = $request->query('status', 'canceled'); // default ke batal/kepepet

        DB::beginTransaction();
        try {
            $saving = $user->savings()->findOrFail($id);

            if ($saving->current_amount > 0) {
                $account = FinancialAccount::findOrFail($saving->financial_account_id);
                $account->balance += $saving->current_amount;
                $account->save();

                $category = $this->getSavingCategory($user->id, 'income');

                // Tentukan deskripsi berdasarkan tombol apa yang dipencet Abang
                $desc = $status === 'completed'
                    ? 'Target Tercapai & Cair: ' . $saving->name
                    : 'Batal/Kepepet Cair: ' . $saving->name;

                $user->transactions()->create([
                    'category_id' => $category->id,
                    'financial_account_id' => $account->id,
                    'amount' => $saving->current_amount,
                    'type' => 'income',
                    'date' => now()->toDateString(),
                    'description' => $desc
                ]);
            }

            $saving->delete();
            DB::commit();
            return response()->json(['message' => 'Target diselesaikan/dihapus']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses pencairan'], 500);
        }
    }
}
