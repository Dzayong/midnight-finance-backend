<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat 1 User Tetap (Biar gampang pas Login di Postman)
        $user = User::create([
            'name' => 'Sultan Keuangan',
            'email' => 'sultan@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active', // 👈 Tambahkan ini biar Sultan langsung aktif
        ]);

        // 2. Daftar Kategori (Sesuai datamu)
        $categoriesData = [
            ['name' => 'Makan & Minum', 'type' => 'expense'],
            ['name' => 'Transportasi', 'type' => 'expense'],
            ['name' => 'Gaji/Penghasilan', 'type' => 'income'],
            ['name' => 'Belanja & Hiburan', 'type' => 'expense'],
            ['name' => 'Tagihan & Kebutuhan Rumah', 'type' => 'expense'],
        ];

        foreach ($categoriesData as $cat) {
            Category::create([
                'user_id' => $user->id,
                'name' => $cat['name'],
                'type' => $cat['type'],
            ]);
        }

        // 3. Daftar Dompet & Bank (Sesuai datamu + Saldo Awal Acak)
        $accountsData = [
            ['name' => 'BCA', 'type' => 'bank', 'balance' => 5000000],
            ['name' => 'Mandiri', 'type' => 'bank', 'balance' => 3000000],
            ['name' => 'BSI', 'type' => 'bank', 'balance' => 2000000],
            ['name' => 'BTN', 'type' => 'bank', 'balance' => 1500000],
            ['name' => 'BRI', 'type' => 'bank', 'balance' => 2500000],
            ['name' => 'BNI', 'type' => 'bank', 'balance' => 1800000],
            ['name' => 'CIMB Niaga', 'type' => 'bank', 'balance' => 1200000],
            // Strip-nya dihilangkan biar cocok sama database:
            ['name' => 'GoPay', 'type' => 'ewallet', 'balance' => 500000],
            ['name' => 'OVO', 'type' => 'ewallet', 'balance' => 300000],
            ['name' => 'ShopeePay', 'type' => 'ewallet', 'balance' => 750000],
            ['name' => 'Dana', 'type' => 'ewallet', 'balance' => 200000],
            ['name' => 'LinkAja', 'type' => 'ewallet', 'balance' => 150000],
        ];

        foreach ($accountsData as $acc) {
            FinancialAccount::create([
                'user_id' => $user->id,
                'name' => $acc['name'],
                'type' => $acc['type'],
                'balance' => $acc['balance'],
            ]);
        }

        // 4. Bikin 50 Transaksi Palsu Pakai Faker
        $faker = Faker::create('id_ID'); // Pakai Faker versi Indonesia
        $categories = Category::where('user_id', $user->id)->get();
        $accounts = FinancialAccount::where('user_id', $user->id)->get();

        for ($i = 0; $i < 50; $i++) {
            // Ambil kategori dan dompet secara acak
            $randomCategory = $categories->random();
            $randomAccount = $accounts->random();

            // Logika nominal: Pengeluaran (10rb - 500rb), Pemasukan (1jt - 10jt)
            $amount = $randomCategory->type === 'expense'
                ? $faker->numberBetween(10, 500) * 1000
                : $faker->numberBetween(10, 100) * 100000;

            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $randomCategory->id,
                'financial_account_id' => $randomAccount->id,
                'amount' => $amount,
                'type' => $randomCategory->type,
                // Tanggal acak dari 2 bulan lalu sampai hari ini
                'date' => $faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
                'description' => $faker->sentence(3),
            ]);
        }
    }
}
