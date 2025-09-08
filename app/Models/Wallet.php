<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'amount',
        'created_by',
        'families',
        'type'
    ];

    public function accesses(): HasMany
    {
        return $this->hasMany(WalletAccess::class, 'wallets');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'families');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallets');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallets');
    }
}
