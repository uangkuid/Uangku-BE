<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletSnapshot extends BaseModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'wallet',
        'wallet_transaction',
        'balance',
    ];
}
