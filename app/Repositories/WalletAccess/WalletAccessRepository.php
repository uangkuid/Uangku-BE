<?php

namespace App\Repositories\WalletAccess;

use App\Models\WalletAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface WalletAccessRepository extends Repository
{
    /**
     * Get wallet access for a user.
     */
    public function getWalletPaging(string $userId, int $perPage = 10, ?string $familyId = null): LengthAwarePaginator;

    /**
     * Check if a user has admin access to a wallet.
     */
    public function isHasAdminAccess(string $userId, string $walletId): bool;

    /**
     * Check if a user has access to a wallet.
     */
    public function isHasAccess(string $userId, string $walletId): bool;

    /**
     * Check if a user has access a wallet before.
     */
    public function isHasAccessBefore(string $userId, string $walletId): bool;

    /**
     * Get a list of users who have access to a specific wallet.
     */
    public function getAccessPaging(string $walletId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Revoke access for a user to a specific wallet.
     */
    public function revokeAccess(string $walletId, string $userId): void;

    /**
     * Grant access for a user to a specific wallet.
     */
    public function grantAccess(string $walletId, string $userId): void;

    /**
     * Get access model a user on a specific wallet.
     */
    public function getDetailAccess(string $walletId, string $userId): ?WalletAccess;
}
