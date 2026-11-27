<?php

namespace App\Repositories\WalletAccess;

use App\Enums\RoleWallet;
use App\Models\WalletAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Implementations\Eloquent;

class WalletAccessRepositoryImplement extends Eloquent implements WalletAccessRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected WalletAccess $model;

    public function __construct(WalletAccess $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Get wallet access for a user.
     */
    public function getWalletPaging(string $userId, int $perPage = 10, ?string $familyId = null): LengthAwarePaginator
    {
        if ($familyId != null) {
            return $this->model
                ->select('id', 'users', 'wallets', 'role', 'is_active', 'created_at', 'updated_at')
                ->where('users', $userId)
                ->with('wallet:id,name,amount,type,status,created_at,updated_at')
                ->paginate($perPage);
        } else {
            return $this->model
                ->select('id', 'users', 'wallets', 'role', 'is_active', 'created_at', 'updated_at')
                ->where('users', $userId)
                ->whereHas('wallet', function ($query) {
                    $query->whereNull('families');
                })
                ->with('wallet:id,name,amount,type,status,created_at,updated_at')
                ->paginate($perPage);
        }
    }

    /**
     * Check if a user has admin access to a wallet.
     */
    public function isHasAdminAccess(string $userId, string $walletId): bool
    {
        return $this->model
            ->select('id')
            ->where('users', $userId)
            ->where('wallets', $walletId)
            ->where('role', RoleWallet::Admin)
            ->where('is_active', true)
            ->limit(1)
            ->exists();
    }

    /**
     * Check if a user has access to a wallet.
     */
    public function isHasAccess(string $userId, string $walletId): bool
    {
        return $this->model
            ->select('id')
            ->where('users', $userId)
            ->where('wallets', $walletId)
            ->where('is_active', true)
            ->limit(1)
            ->exists();
    }

    /**
     * Get a list of users who have access to a specific wallet.
     */
    public function getAccessPaging(string $walletId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select('id', 'users', 'wallets', 'role', 'is_active', 'created_at', 'updated_at')
            ->where('wallets', $walletId)
            ->where('is_active', true)
            ->with('user:id,email,avatar')
            ->paginate($perPage);
    }

    /**
     * Revoke access for a user to a specific wallet.
     */
    public function revokeAccess(string $walletId, string $userId): void
    {
        $this->model
            ->where('wallets', $walletId)
            ->where('users', $userId)
            ->update([
                'is_active' => false,
            ]);
    }

    /**
     * Check if a user has access a wallet before.
     */
    public function isHasAccessBefore(string $userId, string $walletId): bool
    {
        return $this->model
            ->select('id')
            ->where('users', $userId)
            ->where('wallets', $walletId)
            ->where('is_active', false)
            ->limit(1)
            ->exists();
    }

    /**
     * Grant access for a user to a specific wallet.
     */
    public function grantAccess(string $walletId, string $userId): void
    {
        $this->model
            ->where('wallets', $walletId)
            ->where('users', $userId)
            ->update(['is_active' => true]);
    }

    /**
     * Get access model a user on a specific wallet.
     */
    public function getDetailAccess(string $walletId, string $userId): ?WalletAccess
    {
        return $this->model
            ->select('id', 'users', 'wallets', 'role', 'is_active', 'created_at', 'updated_at')
            ->where('wallets', $walletId)
            ->where('users', $userId)
            ->limit(1)
            ->first();
    }
}
