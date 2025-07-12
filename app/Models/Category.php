<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends BaseModel
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'name',
        'transaction_types',
        'icon'
    ];
    protected $defaultSelect = [
        'id',
        'name',
        'icon',
        'transaction_types',
        'created_at',
        'updated_at'
    ];

    public function newQuery(): Builder
    {
        return parent::newQuery()->select($this->defaultSelect);
    }

    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes()->select($this->defaultSelect);
    }

    public function transactionTypes(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_types');
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class, 'categories', 'id');
    }
}
