<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConfig extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
       'users',
       'is_pin_enabled',
       'start_date_month'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'users',
    ];

    public function users(): BelongsTo {
        return $this->belongsTo(User::class, 'users', 'id');
    }
}
