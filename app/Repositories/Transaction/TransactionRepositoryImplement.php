<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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
    ): LengthAwarePaginator
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
            ->where('users', $userId)
            ->when($familyId, function ($query) use ($familyId) {
                return $query->where('families', $familyId);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where('note', 'like', '%' . $search . '%');
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->where('categories', $categoryId);
            })
            ->when($transactionTypeId, function ($query) use ($transactionTypeId) {
                return $query->where('transaction_type', $transactionTypeId);
            })
            ->when($walletId, function ($query) use ($walletId) {
                return $query->where('wallets', $walletId);
            })
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
