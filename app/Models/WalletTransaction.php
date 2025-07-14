<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends BaseModel
{

    use HasFactory, HasUuids;

    protected $fillable = [
        'wallets',
        'access',
        'transaction_type',
        'amount'
    ];

    protected $defaultSelect = [
        'id',
        'wallets',
        'access',
        'transaction_type',
        'amount',
        'created_at',
        'updated_at',
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
