<?php

namespace App\Repositories\WalletTransaction;

use App\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface WalletTransactionRepository extends Repository{

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
        string $transactionType,
        string $userId,
    ): WalletTransaction;

    function getTransactionPaging(
        string $walletId,
        int $perPage = 10
    ): LengthAwarePaginator;
}
