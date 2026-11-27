<?php

namespace App\Repositories\WalletSnapshot;

use App\Models\WalletSnapshot;
use LaravelEasyRepository\Repository;

interface WalletSnapshotRepository extends Repository
{
    /**
     * Check if a snapshot exists for a given wallet ID.
     */
    public function isHasSnapshot(string $walletId): bool;

    /**
     * Get the last snapshot for a given wallet ID.
     */
    public function getLastSnapshot(string $walletId): ?WalletSnapshot;

    /**
     * Create a new wallet snapshot.
     */
    public function createWalletSnapshot(
        string $wallet,
        string $walletTransaction,
        string $balance,
    ): WalletSnapshot;
}
