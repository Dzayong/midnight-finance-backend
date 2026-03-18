<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionsImport implements ToCollection, WithHeadingRow
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                if (!isset($row['tanggal_yyyy_mm_dd']) || empty($row['tanggal_yyyy_mm_dd'])) {
                    continue;
                }

                // TRIK SAKTI: Potong teks "1 - BCA" menjadi array, lalu ambil elemen pertama (angka 1)
                $kategoriId = explode(' - ', $row['kategori'])[0];
                $dompetId = explode(' - ', $row['dompet'])[0];

                // 1. Catat Transaksi
                Transaction::create([
                    'user_id' => $this->userId,
                    'date' => $row['tanggal_yyyy_mm_dd'],
                    'category_id' => $kategoriId, // Masukkan angka ID yang sudah dipotong
                    'financial_account_id' => $dompetId, // Masukkan angka ID yang sudah dipotong
                    'amount' => $row['nominal'],
                    'type' => $row['tipe'],
                    'description' => $row['keterangan'] ?? null,
                ]);

                // 2. Update Saldo Dompet
                $account = FinancialAccount::where('id', $dompetId)
                    ->where('user_id', $this->userId)
                    ->first();

                if (!$account) {
                    throw new Exception("Gagal di baris ke-" . ($index + 2) . ": Dompet tidak valid.");
                }

                if ($row['tipe'] === 'expense') {
                    $account->balance -= $row['nominal'];
                } else {
                    $account->balance += $row['nominal'];
                }
                $account->save();
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
