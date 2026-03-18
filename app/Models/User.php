<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'otp_code',
        'otp_expires_at',
        'avatar',
        'avatar_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',     // Sebaiknya di-cast juga agar mudah dimanipulasi
            'avatar_updated_at' => 'datetime',  // Digabung ke sini
        ];
    }

    // --- Accessors ---

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? asset('storage/' . $value) : null,
        );
    }

    // --- Relationships ---

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function financialAccounts()
    {
        return $this->hasMany(FinancialAccount::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function savings()
    {
        return $this->hasMany(Saving::class);
    }
}
