<?php

namespace App\Repositories\WalletTransaction;

use App\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Implementations\Eloquent;

class WalletTransactionRepositoryImplement extends Eloquent implements WalletTransactionRepository
{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected WalletTransaction $model;

    public function __construct(WalletTransaction $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new wallet transaction.
     * @param string $accessId
     * @param string $walletId
     * @param string $amount
     * @param string $transactionType
     * @return WalletTransaction
     */
    function createTransaction(
        string $accessId,
        string $walletId,
        string $amount,
        string $transactionType
    ): WalletTransaction
    {

        return $this->model->create([
            'access' => $accessId,
            'wallets' => $walletId,
            'amount' => $amount,
            'transaction_type' => $transactionType,
        ]);
    }

    function getTransactionPaging(string $walletId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select('id', 'access', 'wallets', 'amount', 'transaction_type', 'created_at', 'updated_at')
            ->where('wallets', $walletId)
            ->paginate($perPage);
    }
}
