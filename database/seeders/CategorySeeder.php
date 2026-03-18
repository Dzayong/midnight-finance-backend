<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [

            ['name' => 'Gaji', 'type' => 'income'],
            ['name' => 'Bonus', 'type' => 'income'],
            ['name' => 'Investasi', 'type' => 'income'],
            ['name' => 'Dikasih', 'type' => 'income'],
            ['name' => 'Beasiswa', 'type' => 'income'],

            ['name' => 'Makanan', 'type' => 'expense'],
            ['name' => 'Transportasi', 'type' => 'expense'],
            ['name' => 'Belanja', 'type' => 'expense'],
            ['name' => 'Tagihan', 'type' => 'expense'],
            ['name' => 'Hiburan', 'type' => 'expense'],
            ['name' => 'Apresiasi Diri', 'type' => 'expense'],
            ['name' => 'Kebutuhan Hidup', 'type' => 'expense'],

        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
