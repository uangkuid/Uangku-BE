<?php

namespace App\Repositories\WalletSnapshot;

use App\Models\WalletSnapshot;
use LaravelEasyRepository\Repository;

interface WalletSnapshotRepository extends Repository{

    /**
     * Check if a snapshot exists for a given wallet ID.
     * @param string $walletId
     * @return bool
     */
    function isHasSnapshot(string $walletId): bool;

    /**
     * Get the last snapshot for a given wallet ID.
     * @param string $walletId
     * @return WalletSnapshot|null
     */
    function getLastSnapshot(string $walletId): ?WalletSnapshot;

    /**
     * Create a new wallet snapshot.
     * @param string $wallet
     * @param string $walletTransaction
     * @param string $balance
     * @return WalletSnapshot
     */
    function createWalletSnapshot(
        string $wallet,
        string $walletTransaction,
        string $balance,
    ): WalletSnapshot;
}
