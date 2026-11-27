<?php

namespace App\Repositories\WalletSnapshot;

use App\Models\WalletSnapshot;
use LaravelEasyRepository\Implementations\Eloquent;

class WalletSnapshotRepositoryImplement extends Eloquent implements WalletSnapshotRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected WalletSnapshot $model;

    public function __construct(WalletSnapshot $model)
    {
        $this->model = $model;
    }

    /**
     * Check if a snapshot exists for a given wallet ID.
     */
    public function isHasSnapshot(string $walletId): bool
    {
        return $this->model
            ->select('id')
            ->where('wallet', $walletId)
            ->exists();
    }

    /**
     * Get the last snapshot for a given wallet ID.
     */
    public function getLastSnapshot(string $walletId): ?WalletSnapshot
    {
        return $this->model
            ->where('wallet', $walletId)
            ->latest('created_at')
            ->first();
    }

    /**
     * Create a new wallet snapshot.
     */
    public function createWalletSnapshot(
        string $wallet,
        string $walletTransaction,
        string $balance,
    ): WalletSnapshot {
        return $this->model->create([
            'wallet' => $wallet,
            'wallet_transaction' => $walletTransaction,
            'balance' => $balance,
        ]);
    }
}
