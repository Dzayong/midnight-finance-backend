<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_account_id',
        'to_account_id',
        'amount',
        'date',
        'description'
    ];

    // Relasi opsional biar gampang narik data nantinya
    public function fromAccount() { return $this->belongsTo(FinancialAccount::class, 'from_account_id'); }
    public function toAccount() { return $this->belongsTo(FinancialAccount::class, 'to_account_id'); }
}
