<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $fillable = [
        'user_id',
        'financial_account_id', // 👈 Tambahan baru
        'name',
        'amount',
        'type',
        'status',
        'due_date',
        'description'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function financialAccount() { return $this->belongsTo(FinancialAccount::class); } // 👈 Tambahan baru
}
