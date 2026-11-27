<?php

namespace App\Services\Transaction;

use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LaravelEasyRepository\BaseService;

interface TransactionService extends BaseService
{
    /**
     * Create a new transaction.
     *
     * @throws EncryptionException
     * @throws FamilyException
     * @throws GeneralException
     */
    public function createTransaction(
        string $userId,
        string $categoryId,
        string $walletId,
        string $transactionTypeId,
        string $amount,
        ?string $snapshotId = null,
        ?string $description = null,
        ?string $family = null,
        ?string $subCategoryId = null,
        ?string $transactionId = null
    ): Transaction;

    /**
     * Update an existing transaction.
     *
     * @return void
     */
    public function updateTransaction(
        string $id,
        string $userId,
        string $categoryId,
        string $walletId,
        string $amount,
        string $snapshotId,
        ?string $description = null,
        ?string $subCategoryId = null,
    );

    /**
     * Get a transaction by its ID.
     */
    public function getDetailTransaction(
        string $id
    ): ?Transaction;

    /**
     * Delete a transaction.
     */
    public function deleteTransaction(
        string $id,
        string $walletId,
        string $snapshotId,
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
    ): AnonymousResourceCollection;
}
