<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKey extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'public_key',
        'private_key',
        'users',
        'salt',
        'hashed_pin',
    ];
}
