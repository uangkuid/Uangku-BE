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
     * @param string $userId
     * @return WalletTransaction
     */
    function createTransaction(
        string $accessId,
        string $walletId,
        string $amount,
        string $transactionType,
        string $userId
    ): WalletTransaction
    {

        return $this->model->create([
            'access' => $accessId,
            'wallets' => $walletId,
            'amount' => $amount,
            'transaction_type' => $transactionType,
            'updated_by' => $userId
        ]);
    }

    function getTransactionPaging(string $walletId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select('id', 'access', 'wallets', 'amount', 'transaction_type', 'created_at', 'updated_at')
            ->where('wallets', $walletId)
            ->paginate($perPage);
    }

    /**
     * Update an existing wallet transaction.
     * @param string $id
     * @param string $amount
     * @param string $userId
     * @return void
     */
    function updateTransaction(
        string $id,
        string $amount,
        string $userId
    ): void
    {
        $this->model->where('id', $id)
            ->update([
                'amount' => $amount,
                'updated_by' => $userId
            ]);
    }
}
