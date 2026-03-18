<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'balance'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
