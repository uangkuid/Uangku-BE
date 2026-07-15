<?php

namespace App\Services\Wallet;

use App\Enums\RoleWallet;
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
     */
    public function grantAccess(
        string $userId,
        string $walletId,
        RoleWallet $accessType
    ): WalletAccess;

    /**
     * Get wallet access for a user.
     */
    public function getWalletAccess(string $userId): array;

    /**
     * Create a new wallet for a user. $name/$amount are client ciphertext.
     *
     * @throws FamilyException
     * @throws UserException
     * @throws GeneralException
     */
    public function createWallet(
        string $name,
        string $amount,
        string $userId,
        ?string $familyId = null
    ): array;

    /**
     * Get a list of wallets for a user.
     */
    public function getWallet(
        string $userId,
        int $perPage = 10,
        ?string $familyId = null,
    ): AnonymousResourceCollection;

    /**
     * Check if a user has admin access to a wallet.
     */
    public function isHasAdminAccess(
        string $walletId,
        string $userId,
        ?string $familyId = null
    ): bool;

    /**
     * Update a wallet's data. $name is client ciphertext.
     *
     * @throws FamilyException
     * @throws UserException
     * @throws GeneralException
     */
    public function updateWallet(
        string $walletId,
        string $name,
        ?string $familyId = null
    ): void;

    /**
     * Update the status of a wallet.
     */
    public function updateWalletStatus(
        string $walletId,
        string $status
    ): void;

    /**
     * Check if a user has access to a wallet.
     */
    public function isHasAccess(
        string $walletId,
        string $userId
    ): bool;

    /**
     * Get a list of users who have access to a specific wallet.
     */
    public function getMember(string $id, int $perPage = 10): AnonymousResourceCollection;

    /**
     * Get a list of family members who have access to a specific wallet.
     *
     * @throws GeneralException
     */
    public function getFamilyNotJoinWallet(string $id, int $perPage = 10): AnonymousResourceCollection;

    /**
     * Add a member to a wallet.
     *
     * @throws GeneralException
     */
    public function addMember(string $id, string $userId): array;

    /**
     * Revoke a user's access to a wallet.
     */
    public function revokeMember(string $id, string $userId): void;

    /**
     * Create a new wallet transaction.
     */
    public function createWalletTransaction(
        string $userId,
        string $walletId,
        string $amount,
        string $transactionTypeId,
        string $transactionId,
        ?string $family = null,
    ): WalletTransaction;

    /**
     * Get latest wallet snapshot for a specific wallet.
     */
    public function getLatestSnapshot(
        string $walletId
    ): ?WalletSnapshot;

    /**
     * Create a wallet snapshot.
     *
     * @throws GeneralException
     */
    public function createWalletSnapshot(
        string $wallet,
        string $walletTransaction,
        string $amount,
        ?string $balance = null,
        ?string $snapshotId = null
    ): WalletSnapshot;

    /**
     * Update an existing wallet transaction.
     */
    public function updateWalletTransaction(
        string $id,
        string $amount,
        string $userId,
    ): void;

    /**
     * Get detailed information about a specific wallet transaction.
     */
    public function getDetailWalletTransaction(
        string $id
    ): ?WalletTransaction;

    /**
     * Delete a wallet transaction.
     */
    public function deleteWalletTransaction(
        string $id,
        string $userId
    ): void;

    /**
     * Get detailed wallet transaction by transaction ID.
     */
    public function getDetailWalletTransactionByTransactionId(
        string $transactionId
    ): ?WalletTransaction;
}
