<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'categories',
        'users',
        'families'
    ];
    protected $defaultSelect = [
        'id',
        'name',
        'categories',
        'users',
        'families',
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

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class, 'categories');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'users');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'families');
    }
}
