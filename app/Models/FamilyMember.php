<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user',
        'family',
        'role'
    ];

    public function families(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user');
    }
}
