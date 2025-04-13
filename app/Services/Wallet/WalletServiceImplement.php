<?php

namespace App\Services\Wallet;

use App\Enums\RoleWallet;
use App\Models\WalletAccess;
use LaravelEasyRepository\Service;
use App\Repositories\Wallet\WalletRepository;

class WalletServiceImplement extends Service implements WalletService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected WalletRepository $mainRepository;
     private WalletAccess $access;

    public function __construct(WalletRepository $mainRepository, WalletAccess $access)
    {
        $this->mainRepository = $mainRepository;
        $this->access = $access;
    }
    /**
     * Grant access to a user for a specific wallet.
     *
     * @param string $userId
     * @param string $walletId
     * @param RoleWallet $accessType
     * @return WalletAccess
     */
    function grantAccess(string $userId, string $walletId, RoleWallet $accessType): WalletAccess
    {
        return $this->access->create([
            'users' => $userId,
            'wallets' => $walletId,
            'role' => $accessType,
            'is_active' => true,
        ]);
    }
}
