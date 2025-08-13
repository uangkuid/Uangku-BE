<?php

namespace App\Services\Transaction;

use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
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
     * @param string|null $snapshotId
     * @param string|null $description
     * @param string|null $family
     * @param string|null $subCategoryId
     * @param string|null $transactionId
     * @return Transaction
     * @throws EncryptionException
     * @throws FamilyException
     * @throws GeneralException
     */
    function createTransaction(
        string $userId,
        string $categoryId,
        string $walletId,
        string $transactionTypeId,
        string $amount,
        string $walletTransactionId,
        ?string $snapshotId = null,
        ?string $description = null,
        ?string $family = null,
        ?string $subCategoryId = null,
        ?string $transactionId = null
    ): Transaction;

    /**
     * Update an existing transaction.
     * @param string $id
     * @param string $userId
     * @param string $categoryId
     * @param string $walletId
     * @param string $amount
     * @param string $snapshotId
     * @param string|null $description
     * @param string|null $subCategoryId
     * @return void
     */
    function updateTransaction(
        string $id,
        string $userId,
        string $categoryId,
        string $walletId,
        string $amount,
        string $snapshotId,
        ?string $description = null,
        string $subCategoryId = null,
    );

    /**
     * Get a transaction by its ID.
     * @param string $id
     * @return Transaction|null
     */
    function getDetailTransaction(
        string $id
    ): ?Transaction;

    /**
     * Delete a transaction.
     * @param string $id
     * @param string $walletId
     * @param string $snapshotId
     * @return void
     */
    function deleteTransaction(
        string $id,
        string $walletId,
        string $snapshotId,
    ): void;
}
