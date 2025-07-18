<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use LaravelEasyRepository\BaseService;

interface TransactionService extends BaseService{

    /**
     * Create a new transaction.
     * @param string $userId
     * @param string $categoryId
     * @param string $walletId
     * @param string $transactionTypeId
     * @param string $amount
     * @param string $walletTransactionId
     * @param string|null $description
     * @param string|null $family
     * @param string|null $subCategoryId
     * @param string|null $transactionId
     * @return Transaction
     */
    function createTransaction(
        string $userId,
        string $categoryId,
        string $walletId,
        string $transactionTypeId,
        string $amount,
        string $walletTransactionId,
        string $description = null,
        string $family = null,
        string $subCategoryId = null,
        string $transactionId = null
    ): Transaction;
}
