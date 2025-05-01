<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'avatar',
        'created_by'
    ];

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family');
    }
}
