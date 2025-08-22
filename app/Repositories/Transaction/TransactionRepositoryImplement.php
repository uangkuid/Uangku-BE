<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use LaravelEasyRepository\Implementations\Eloquent;

class TransactionRepositoryImplement extends Eloquent implements TransactionRepository
{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected Transaction $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

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
    ): Transaction
    {
        $data = [
            'users' => $userId,
            'categories' => $categoryId,
            'transaction_type' => $transactionTypeId,
            'amount' => $amount,
            'note' => $description,
        ];

        if ($family) {
            $data['families'] = $family;
        }

        if ($subCategoryId) {
            $data['sub_categories'] = $subCategoryId;
        }

        if ($transactionId) {
            $data['id'] = $transactionId;
        }

        return $this->model->create($data);
    }

    /**
     * Update an existing transaction.
     * @param string $id
     * @param string $userId
     * @param string $categoryId
     * @param string $walletId
     * @param string $amount
     * @param string|null $description
     * @param string|null $subCategoryId
     */
    public function updateTransaction(
        string  $id,
        string  $userId,
        string  $categoryId,
        string  $amount,
        ?string $description = null,
        ?string $subCategoryId = null
    )
    {
        $data = [
            'users' => $userId,
            'categories' => $categoryId,
            'amount' => $amount,
        ];

        if ($description) {
            $data['note'] = $description;
        }

        if ($subCategoryId) {
            $data['sub_categories'] = $subCategoryId;
        }

        $this->model->where('id', $id)->update($data);
    }

    /**
     * Get a transaction by its ID.
     * @param string $id
     * @return Transaction|null
     */
    function getDetailTransaction(string $id): ?Transaction
    {
        return $this->model
            ->select([
                'id',
                'users',
                'categories',
                'transaction_type',
                'amount',
                'note',
                'sub_categories',
                'created_at',
                'updated_at'
            ])
            ->where('id', $id)
            ->first();
    }

    /**
     * Delete a transaction.
     * @param string $id
     * @return void
     */
    function deleteTransaction(string $id): void
    {
        $this->model->where('id', $id)
            ->delete();
    }
}
