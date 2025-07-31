<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use LaravelEasyRepository\Repository;

interface TransactionRepository extends Repository{

    /**
     * Create a new transaction.
     * @param string $userId
     * @param string $categoryId
     * @param string $walletId
     * @param string $transactionTypeId
     * @param string $amount
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
        string $description = null,
        string $family = null,
        string $subCategoryId = null,
        string $transactionId = null
    ): Transaction;

    /**
     * Update an existing transaction.
     * @param string $id
     * @param string $userId
     * @param string $categoryId
     * @param string $walletId
     * @param string $amount
     * @param string|null $description
     * @param string|null $subCategoryId
     * @return Transaction
     */
    public function updateTransaction(
        string $id,
        string $userId,
        string $categoryId,
        string $walletId,
        string $amount,
        ?string $description = null,
        ?string $subCategoryId = null
    ): Transaction;
}
