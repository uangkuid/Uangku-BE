<?php

namespace App\Services\Wallet;

use App\Enums\RoleWallet;
use App\Models\WalletAccess;
use LaravelEasyRepository\BaseService;

interface WalletService extends BaseService{

    /**
     * Grant access to a user for a specific wallet.
     *
     * @param string $userId
     * @param string $walletId
     * @param RoleWallet $accessType
     * @return WalletAccess
     */
    function grantAccess(
        string $userId,
        string $walletId,
        RoleWallet $accessType
    ): WalletAccess;

    /**
     * Get wallet access for a user.
     * @param string $userId
     * @return array
     */
    function getWalletAccess(string $userId): array;
}
