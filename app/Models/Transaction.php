<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'transaction_type',
        'note',
        'amount',
        'deleted_at',
    ];

    protected $defaultSelect = [
        'id',
        'users',
        'categories',
        'sub_categories',
        'transaction_type',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categories');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_categories');
    }

    /**
     * transactions has no direct wallet/family FK; the link lives on wallet_transactions
     * (wallet_transactions.transaction_id -> transactions.id, wallet_transactions.wallets -> wallets.id).
     */
    public function walletTransaction(): HasOne
    {
        return $this->hasOne(WalletTransaction::class, 'transaction_id');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type');
    }
}
