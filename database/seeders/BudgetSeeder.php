<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        Budget::create([
            'user_id' => 1,
            'category_id' => 4,
            'amount' => 2000000,
            'month' => now()->month,
            'year' => now()->year
        ]);
    }
}
