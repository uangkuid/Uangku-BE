<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends BaseModel
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'name',
        'transaction_types'
    ];

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_types');
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }
}
