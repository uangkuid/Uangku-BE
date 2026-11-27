<?php

namespace App\Repositories\Wallet;

use App\Exceptions\GeneralException;
use App\Models\Wallet;
use LaravelEasyRepository\Repository;

interface WalletRepository extends Repository
{
    /**
     * Get all individual wallets with their access.
     */
    public function getIndividualWallet(string $id): array;

    /**
     * Check if a wallet name already exists.
     */
    public function isNameExist(string $name, ?string $familyId = null): bool;

    /**
     * Create a new wallet.
     */
    public function createWallet(
        string $name,
        string $amount,
        string $userId,
        ?string $familyId = null,
    ): Wallet;

    /**
     * @throws GeneralException
     */
    public function updateWallet(
        string $name,
        string $walletId,
        ?string $familyId = null,
    ): void;

    /**
     * Update the status of a wallet.
     *
     * @param  bool  $status
     */
    public function updateWalletStatus(
        string $walletId,
        string $status
    ): void;
}
