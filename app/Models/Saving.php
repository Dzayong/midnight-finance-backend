<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Saving extends Model
{
    protected $fillable = [
        'user_id',
        'financial_account_id', // 👈 Tambahan baru
        'name',
        'target_amount',
        'current_amount',
        'deadline'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function financialAccount() { return $this->belongsTo(FinancialAccount::class); } // 👈 Tambahan baru
}
