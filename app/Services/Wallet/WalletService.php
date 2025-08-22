<?php

namespace App\Services\Wallet;

use App\Enums\RoleWallet;
use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Models\WalletAccess;
use App\Models\WalletSnapshot;
use App\Models\WalletTransaction;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LaravelEasyRepository\BaseService;

interface WalletService extends BaseService
{

    /**
     * Grant access to a user for a specific wallet.
     *
     * @param string $userId
     * @param string $walletId
     * @param RoleWallet $accessType
     * @return WalletAccess
     */
    function grantAccess(
        string     $userId,
        string     $walletId,
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
        string  $name,
        string  $userId,
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
        string  $userId,
        int     $perPage = 10,
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
        string  $walletId,
        string  $userId,
        ?string $familyId = null
    ): bool;

    /**
     * Update a wallet's data
     * @param string $walletId
     * @param string $name
     * @param string|null $familyId
     * @return void
     * @throws FamilyException
     * @throws UserException|EncryptionException
     * @throws GeneralException
     */
    function updateWallet(
        string  $walletId,
        string  $name,
        ?string $familyId = null
    ): void;

    /**
     * Update the status of a wallet.
     * @param string $walletId
     * @param string $status
     * @return void
     */
    function updateWalletStatus(
        string $walletId,
        string $status
    ): void;

    /**
     * Check if a user has access to a wallet.
     * @param string $walletId
     * @param string $userId
     * @return bool
     */
    function isHasAccess(
        string $walletId,
        string $userId
    ): bool;

    /**
     * Get a list of users who have access to a specific wallet.
     * @param string $id
     * @param int $perPage
     * @return AnonymousResourceCollection
     */
    function getMember(string $id, int $perPage = 10): AnonymousResourceCollection;

    /**
     * Get a list of family members who have access to a specific wallet.
     * @param string $id
     * @param int $perPage
     * @return AnonymousResourceCollection
     * @throws GeneralException
     */
    function getFamilyNotJoinWallet(string $id, int $perPage = 10): AnonymousResourceCollection;

    /**
     * Add a member to a wallet.
     * @param string $id
     * @param string $userId
     * @return array
     * @throws GeneralException
     */
    function addMember(string $id, string $userId): array;

    /**
     * Revoke a user's access to a wallet.
     * @param string $id
     * @param string $userId
     * @return void
     */
    function revokeMember(string $id, string $userId): void;

    /**
     * Create a new wallet transaction.
     * @param string $userId
     * @param string $walletId
     * @param string $amount
     * @param string $transactionTypeId
     * @param string|null $family
     * @return WalletTransaction
     */
    function createWalletTransaction(
        string  $userId,
        string  $walletId,
        string  $amount,
        string  $transactionTypeId,
        string  $transactionId,
        ?string $family = null,
    ): WalletTransaction;

    /**
     * Get latest wallet snapshot for a specific wallet.
     * @param string $walletId
     * @return WalletSnapshot|null
     */
    function getLatestSnapshot(
        string $walletId
    ): ?WalletSnapshot;

    /**
     * Create a wallet snapshot.
     * @param string $wallet
     * @param string $walletTransaction
     * @param string $amount
     * @param string|null $snapshotId
     * @return WalletSnapshot
     * @throws GeneralException
     */
    function createWalletSnapshot(
        string  $wallet,
        string  $walletTransaction,
        string  $amount,
        ?string $balance = null,
        ?string $snapshotId = null
    ): WalletSnapshot;

    /**
     * Get wallet transactions with pagination.
     * @param string $id
     * @param int $perPage
     * @return AnonymousResourceCollection
     */
    function getWalletTransaction(
        string $id,
        int    $perPage = 10
    ): AnonymousResourceCollection;

    /**
     * Update an existing wallet transaction.
     * @param string $id
     * @param string $amount
     * @param string $userId
     * @return void
     */
    function updateWalletTransaction(
        string $id,
        string $amount,
        string $userId,
    ): void;

    /**
     * Get detailed information about a specific wallet transaction.
     * @param string $id
     * @return WalletTransaction|null
     */
    function getDetailWalletTransaction(
        string $id
    ): ?WalletTransaction;

    /**
     * Delete a wallet transaction.
     * @param string $id
     * @param string $userId
     * @return void
     */
    function deleteWalletTransaction(
        string $id,
        string $userId
    ): void;

    /**
     * Get detailed wallet transaction by transaction ID.
     * @param string $transactionId
     * @return WalletTransaction|null
     */
    function getDetailWalletTransactionByTransactionId(
        string $transactionId
    ): ?WalletTransaction;
}
