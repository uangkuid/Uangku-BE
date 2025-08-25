<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends BaseModel
{

    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'wallets',
        'access',
        'transaction_type',
        'amount',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $defaultSelect = [
        'id',
        'wallets',
        'access',
        'transaction_type',
        'transaction_id',
        'amount',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'deleted_by',

    ];

    public function newQuery(): Builder
    {
        return parent::newQuery()->select($this->defaultSelect);
    }

    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes()->select($this->defaultSelect);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallets');
    }

    public function access(): BelongsTo
    {
        return $this->belongsTo(WalletAccess::class, 'access');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type');
    }
}
