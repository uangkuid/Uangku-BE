<?php

namespace App\Services\Wallet;

use App\Enums\RoleWallet;
use App\Models\WalletAccess;
use LaravelEasyRepository\BaseService;

interface WalletService extends BaseService{

    function grantAccess(
        string $userId,
        string $walletId,
        RoleWallet $accessType
    ): WalletAccess;
}
