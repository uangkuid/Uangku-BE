<?php

namespace App\Repositories\Wallet;

use App\Exceptions\GeneralException;
use App\Models\Wallet;
use LaravelEasyRepository\Repository;

interface WalletRepository extends Repository{
    /**
     * Get all individual wallets with their access.
     *
     * @param string $id
     * @return array
     */
    function getIndividualWallet(string $id): array;

    /**
     * Check if a wallet name already exists.
     * @param string $name
     * @param string|null $familyId
     * @return bool
     */
    function isNameExist(string $name, ?string $familyId = null): bool;

    /**
     * Create a new wallet.
     * @param string $name
     * @param string $amount
     * @param string $userId
     * @param string|null $familyId
     * @return Wallet
     */
    function createWallet(
        string $name,
        string $amount,
        string $userId,
        ?string $familyId = null,
    ): Wallet;

    /**
     * @param string $name
     * @param string $walletId
     * @param string|null $familyId
     * @return void
     * @throws GeneralException
     */
    function updateWallet(
        string $name,
        string $walletId,
        ?string $familyId = null,
    ): void;

    /**
     * Update the status of a wallet.
     * @param string $walletId
     * @param bool $status
     * @return void
     */
    function updateWalletStatus(
        string $walletId,
        string $status
    ): void;
}
