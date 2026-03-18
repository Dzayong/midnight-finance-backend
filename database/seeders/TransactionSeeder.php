<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        Transaction::create([
            'user_id' => 1,
            'account_id' => 1,
            'category_id' => 4,
            'amount' => 25000,
            'type' => 'expense',
            'description' => 'Makan Malam',
            'transaction_date' => now()
        ]);

        Transaction::create([
            'user_id' => 1,
            'account_id' => 2,
            'category_id' => 1,
            'amount' => 5000000,
            'type' => 'income',
            'description' => 'Gaji bulanan',
            'transaction_date' => now()
        ]);
    }
}
