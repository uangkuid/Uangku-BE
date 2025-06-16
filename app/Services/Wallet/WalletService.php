<?php

namespace App\Services\Wallet;

use App\Enums\RoleWallet;
use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Models\WalletAccess;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    /**
     * Create a new wallet for a user.
     * @param string $name
     * @param string $userId
     * @param string|null $familyId
     * @return array
     * @throws FamilyException
     * @throws EncryptionException
     * @throws UserException
     * @throws GeneralException
     */
    function createWallet(
        string $name,
        string $userId,
        ?string $familyId = null
    ): array;

    /**
     * Get a list of wallets for a user.
     * @param string $userId
     * @param int $perPage
     * @param string|null $familyId
     * @return AnonymousResourceCollection
     */
    function getWallet(
        string $userId,
        int $perPage = 10,
        ?string $familyId = null,
    ): AnonymousResourceCollection;

    /**
     * Check if a user has admin access to a wallet.
     * @param string $walletId
     * @param string $userId
     * @param string|null $familyId
     * @return bool
     */
    function isHasAdminAccess(
        string $walletId,
        string $userId,
        ?string $familyId = null
    ): bool;
}
