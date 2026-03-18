<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'financial_account_id',
        'amount',
        'type',
        'date',
        'description'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function financialAccount() { return $this->belongsTo(FinancialAccount::class); }
}
