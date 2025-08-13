<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'users',
        'categories',
        'sub_categories',
        'wallets',
        'transaction_type',
        'families',
        'note',
        'amount',
        'deleted_at',
    ];

    protected $defaultSelect = [
        'id',
        'users',
        'categories',
        'sub_categories',
        'wallets',
        'transaction_type',
        'families',
        'note',
        'amount',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function newQuery(): Builder
    {
        return parent::newQuery()->select($this->defaultSelect);
    }

    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes()->select($this->defaultSelect);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Generate UUID hanya jika belum ada ID
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }
}
