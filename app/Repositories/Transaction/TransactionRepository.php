<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface TransactionRepository extends Repository{

    /**
     * Create a new transaction.
     * @param string $userId
     * @param string $categoryId
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
     * @param string $amount
     * @param string|null $description
     * @param string|null $subCategoryId
     */
    public function updateTransaction(
        string $id,
        string $userId,
        string $categoryId,
        string $amount,
        ?string $description = null,
        ?string $subCategoryId = null
    );

    /**
     * Get a transaction by its ID.
     * @param string $id
     * @return Transaction|null
     */
    function getDetailTransaction(string $id): ?Transaction;

    /**
     * Delete a transaction.
     * @param string $id
     * @return void
     */
    function deleteTransaction(
        string $id,
    ): void;

    /**
     * Get a paginated list of transactions for a user.
     * @param string $userId
     * @param string $startDate
     * @param string $endDate
     * @param string|null $familyId
     * @param string|null $search
     * @param string|null $categoryId
     * @param string|null $transactionTypeId
     * @param string|null $walletId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getTransactionPaging(
        string $userId,
        string $startDate,
        string $endDate,
        ?string $familyId = null,
        ?string $search = null,
        ?string $categoryId = null,
        ?string $transactionTypeId = null,
        ?string $walletId = null,
        int $perPage = 10
    ): LengthAwarePaginator;
}
