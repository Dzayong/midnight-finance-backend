<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialAccount;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        FinancialAccount::create([
            'user_id' => 1,
            'name' => 'Dompet Tunai',
            'type' => 'cash',
            'balance' => 171000
        ]);

        FinancialAccount::create([
            'user_id' => 1,
            'name' => 'Bank Syariah Indonesia',
            'type' => 'bank',
            'balance' => 58832
        ]);

        FinancialAccount::create([
            'user_id' => 1,
            'name' => 'Bank Tabungan Negara',
            'type' => 'bank',
            'balance' => 641
        ]);

        FinancialAccount::create([
            'user_id' => 1,
            'name' => 'Seabank',
            'type' => 'bank',
            'balance' => 706084
        ]);

        FinancialAccount::create([
            'user_id' => 1,
            'name' => 'ShopeePay',
            'type' => 'ewallet',
            'balance' => 67016
        ]);

        FinancialAccount::create([
            'user_id' => 1,
            'name' => 'Dana',
            'type' => 'ewallet',
            'balance' => 4018
        ]);
    }
}
