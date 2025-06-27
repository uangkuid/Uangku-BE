<?php

namespace App\Repositories\WalletAccess;

use App\Models\WalletAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface WalletAccessRepository extends Repository{
    /**
     * Get wallet access for a user.
     * @param string $userId
     * @param int $perPage
     * @param string|null $familyId
     * @return LengthAwarePaginator
     */
    function getWalletPaging(string $userId, int $perPage = 10, ?string $familyId = null): LengthAwarePaginator;

    /**
     * Check if a user has admin access to a wallet.
     * @param string $userId
     * @param string $walletId
     * @return bool
     */
    function isHasAdminAccess(string $userId, string $walletId): bool;

    /**
     * Check if a user has access to a wallet.
     * @param string $userId
     * @param string $walletId
     * @return bool
     */
    function isHasAccess(string $userId, string $walletId): bool;

    /**
     * Check if a user has access a wallet before.
     * @param string $userId
     * @param string $walletId
     * @return bool
     */
    function isHasAccessBefore(string $userId, string $walletId): bool;

    /**
     * Get a list of users who have access to a specific wallet.
     * @param string $walletId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getAccessPaging(string $walletId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Revoke access for a user to a specific wallet.
     * @param string $walletId
     * @param string $userId
     * @return void
     */
    function revokeAccess(string $walletId, string $userId): void;

    /**
     * Grant access for a user to a specific wallet.
     * @param string $walletId
     * @param string $userId
     * @return void
     */
    function grantAccess(string $walletId, string $userId): void;

    /**
     * Get access model a user on a specific wallet.
     * @param string $walletId
     * @param string $userId
     * @return WalletAccess|null
     */
    function getDetailAccess(string $walletId, string $userId): ?WalletAccess;
}
