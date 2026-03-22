<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Saving; // 👈 PENTING: Tambahkan ini untuk memanggil tabel Tabungan

class DashboardController extends Controller
{
    /**
     * Data Ringkasan untuk Dashboard Utama
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // 1. Ambil Data Portofolio & Hitung Total Saldo
        $wallets = $user->financialAccounts;
        $totalBalance = $wallets->sum('balance');

        // 2. Ambil Transaksi BULAN INI (Stats Ringkas)
        $transactionsThisMonth = $user->transactions()
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

        $incomeThisMonth = $transactionsThisMonth->where('type', 'income')->sum('amount');
        $expenseThisMonth = $transactionsThisMonth->where('type', 'expense')->sum('amount');

        // 3. Ambil 10 Transaksi Terakhir (Aktivitas Terkini)
        $recentTransactions = $user->transactions()
            ->with(['category', 'financialAccount'])
            ->latest('date')
            ->take(10)
            ->get();

        return response()->json([
            'message' => 'Data Dashboard siap!',
            'data' => [
                'user' => $user,
                'wallets' => $wallets,
                'stats' => [
                    'total_balance' => (float)$totalBalance,
                    'income_this_month' => (float)$incomeThisMonth,
                    'expense_this_month' => (float)$expenseThisMonth,
                    'net_flow' => (float)($incomeThisMonth - $expenseThisMonth)
                ],
                'recent_transactions' => $recentTransactions
            ]
        ], 200);
    }

    /**
     * Data Visual untuk Halaman Analisis (Grafik)
     */
    public function analytics(Request $request)
    {
        $user = $request->user();

        // --- TAHAP A: LOGIKA ZERO-FILLING 7 HARI TERAKHIR ---

        // 1. Inisialisasi array 7 hari terakhir dengan nilai default 0
        $chartData = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartData->put($date, [
                'date' => $date,
                'income' => 0,
                'expense' => 0
            ]);
        }

        // 2. Ambil data asli dari Database
        $actualData = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('date', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(date) as date_only'),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            )
            ->groupBy('date_only')
            ->get();

        // 3. Timpa (Merge) data 0 tadi dengan data asli dari database
        foreach ($actualData as $row) {
            $chartData->put($row->date_only, [
                'date' => $row->date_only,
                'income' => (float)$row->income,
                'expense' => (float)$row->expense
            ]);
        }

        // 4. Ambil Data Alokasi Kategori (Pie Chart)
        $pieChart = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('categories.name')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->name,
                    'total' => (float)$item->total
                ];
            });

        // 💡 5. TAHAP BARU: AMBIL DATA TABUNGAN (SAVINGS)
        $savings = Saving::where('user_id', $user->id)->get();

        return response()->json([
            'line_chart' => $chartData->values(),
            'pie_chart' => $pieChart,
            'savings' => $savings // 👈 Data langsung dilempar ke Frontend!
        ]);
    }
}
