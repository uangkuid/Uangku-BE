<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'amount',
        'created_by',
        'families'
    ];

    public function access(): HasMany
    {
        return $this->hasMany(WalletAccess::class, 'wallets');
    }
}
