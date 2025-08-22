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
     * @param string $transactionId
     * @param string $userId
     * @return WalletTransaction
     */
    function createTransaction(
        string $accessId,
        string $walletId,
        string $amount,
        string $transactionType,
        string $transactionId,
        string $userId,
    ): WalletTransaction;

    /**
     * Get a wallet transaction by its ID.
     * @param string $walletId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getTransactionPaging(
        string $walletId,
        int $perPage = 10
    ): LengthAwarePaginator;

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
    ): void;

    /**
     * Get detail wallet transactions
     * @param string $id
     * @return WalletTransaction|null
     */
    function getDetailWalletTransaction(
        string $id
    ): ?WalletTransaction;

    /**
     * Delete a wallet transaction by its ID.
     * @param string $id
     * @param string $userId
     * @return void
     */
    function deleteTransaction(
        string $id,
        string $userId
    ): void;

    /**
     * Get detailed wallet transaction by transaction ID.
     * @param string $transactionId
     * @return WalletTransaction|null
     */
    function getDetailWalletTransactionByTransactionId(
        string $transactionId
    ): ?WalletTransaction;
}
