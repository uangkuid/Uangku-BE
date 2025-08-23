<?php

namespace App\Repositories\WalletTransaction;

use App\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use LaravelEasyRepository\Implementations\Eloquent;

class WalletTransactionRepositoryImplement extends Eloquent implements WalletTransactionRepository
{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected WalletTransaction $model;

    public function __construct(WalletTransaction $model)
    {
        $this->model = $model;
    }

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
        string $userId
    ): WalletTransaction
    {

        return $this->model->create([
            'access' => $accessId,
            'wallets' => $walletId,
            'amount' => $amount,
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'updated_by' => $userId
        ]);
    }

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
    ): void
    {
        $this->model->where('id', $id)
            ->update([
                'amount' => $amount,
                'updated_by' => $userId
            ]);
    }

    /**
     * Get detail wallet transactions
     * @param string $id
     * @return WalletTransaction|null
     */
    function getDetailWalletTransaction(string $id): ?WalletTransaction
    {
        return $this->model
            ->select('id', 'access', 'wallets', 'amount', 'transaction_type', 'created_at', 'updated_at')
            ->where('id', $id)
            ->first();
    }

    /**
     * Delete a wallet transaction by its ID.
     * @param string $id
     * @param string $userId
     * @return void
     */
    function deleteTransaction(string $id, string $userId): void
    {
        $this->model->where('id', $id)->update([
            'deleted_at' => now(),
            'deleted_by' => $userId,
        ]);
    }

    /**
     * Get detailed wallet transaction by transaction ID.
     * @param string $transactionId
     * @return WalletTransaction|null
     */
    function getDetailWalletTransactionByTransactionId(string $transactionId): ?WalletTransaction
    {
        return $this->model
            ->select('id', 'access', 'wallets', 'amount', 'transaction_type', 'created_at', 'updated_at')
            ->where('transaction_id', $transactionId)
            ->first();
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
    ): LengthAwarePaginator {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();
        $query = $this->model
            ->select([
//                'wallet_transactions.*',
                'transactions.id',
                'transactions.users',
                'transactions.categories',
                'transactions.transaction_type',
                'transactions.amount',
                'transactions.note',
                'transactions.sub_categories',
                'transactions.created_at',
                'transactions.updated_at',
                'wallet_transactions.wallets as wallet_id',
                'categories.name as category_name',
                'categories.icon as category_icon',
                'sub_categories.name as sub_category_name',
                'wallets.name as wallet_name',
            ])
            ->join('wallet_accesses as wa', 'wa.id', '=', 'wallet_transactions.access')
            ->join('wallets', 'wallets.id', '=', 'wallet_transactions.wallets')
            ->join('transactions', 'transactions.id', '=', 'wallet_transactions.transaction_id')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.categories')
            ->leftJoin('sub_categories', 'sub_categories.id', '=', 'transactions.sub_categories')
            ->where('wa.users', $userId)
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->whereNull('transactions.deleted_at');

        if ($familyId) {
            $query->where('wallets.families', $familyId);
        }

        if ($walletId) {
            $query->where('wallets.id', $walletId);
        }

        if ($categoryId) {
            $query->where('transactions.categories', $categoryId);
        }

        if ($transactionTypeId) {
            $query->where('transactions.transaction_type', $transactionTypeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transactions.note', 'like', "%{$search}%")
                    ->orWhere('categories.name', 'like', "%{$search}%")
                    ->orWhere('sub_categories.name', 'like', "%{$search}%")
                    ->orWhere('wallets.name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('transactions.created_at', 'desc')
            ->paginate($perPage);
    }
}
