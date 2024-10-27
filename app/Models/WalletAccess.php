<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletAccess extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'users',
        'wallets',
        'is_active',
        'role'
    ];
}
