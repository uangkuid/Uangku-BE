<?php

namespace App\Repositories\WalletTransaction;

use App\Models\WalletTransaction;
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
    ): WalletTransaction;

}
