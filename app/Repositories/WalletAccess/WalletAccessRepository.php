<?php

namespace App\Repositories\WalletAccess;

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
}
