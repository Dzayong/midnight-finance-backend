<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Saving;

class SavingSeeder extends Seeder
{
    public function run(): void
    {
        Saving::create([
            'user_id' => 1,
            'name' => 'Kuliah Kerja Nyata',
            'target_amount' => 2000000,
            'current_amount' => 500000,
            'target_date' => '2026-06-06'
        ]);
    }
}
