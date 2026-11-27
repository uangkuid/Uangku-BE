<?php

namespace App\Repositories\WalletTransaction;

use App\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface WalletTransactionRepository extends Repository
{
    /**
     * Create a new wallet transaction.
     */
    public function createTransaction(
        string $accessId,
        string $walletId,
        string $amount,
        string $transactionType,
        string $transactionId,
        string $userId,
    ): WalletTransaction;

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

    /**
     * Update an existing wallet transaction.
     */
    public function updateTransaction(
        string $id,
        string $amount,
        string $userId
    ): void;

    /**
     * Get detail wallet transactions
     */
    public function getDetailWalletTransaction(
        string $id
    ): ?WalletTransaction;

    /**
     * Delete a wallet transaction by its ID.
     */
    public function deleteTransaction(
        string $id,
        string $userId
    ): void;

    /**
     * Get detailed wallet transaction by transaction ID.
     */
    public function getDetailWalletTransactionByTransactionId(
        string $transactionId
    ): ?WalletTransaction;
}
