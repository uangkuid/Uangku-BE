<?php

namespace App\Repositories\Wallet;

use App\Models\WalletAccess;
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
}
