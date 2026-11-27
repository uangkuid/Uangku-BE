<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface TransactionRepository extends Repository
{
    /**
     * Create a new transaction.
     */
    public function createTransaction(
        string $userId,
        string $categoryId,
        string $transactionTypeId,
        string $amount,
        ?string $description = null,
        ?string $family = null,
        ?string $subCategoryId = null,
        ?string $transactionId = null
    ): Transaction;

    /**
     * Update an existing transaction.
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
     */
    public function getDetailTransaction(string $id): ?Transaction;

    /**
     * Delete a transaction.
     */
    public function deleteTransaction(
        string $id,
    ): void;

    /**
     * Get a paginated list of transactions for a user.
     */
    public function getTransactionPaging(
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
