<?php

namespace App\Repositories\Transaction;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Transaction;

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
    ): Transaction
    {
        $data = [
            'users' => $userId,
            'categories' => $categoryId,
            'wallets' => $walletId,
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
}
