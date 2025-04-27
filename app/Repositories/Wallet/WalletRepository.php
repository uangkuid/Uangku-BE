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
}
