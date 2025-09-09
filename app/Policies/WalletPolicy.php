<?php

namespace App\Policies;

use App\Models\StaffAccount;
use App\Models\Wallet;
use Illuminate\Auth\Access\Response;

class WalletPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(StaffAccount $user): bool
    {
        return true; // Staff can view wallets
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(StaffAccount $user, Wallet $wallet): bool
    {
        return true; // Staff can view individual wallets
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(StaffAccount $user): bool
    {
        return false; // Staff cannot create wallets - read only
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(StaffAccount $user, Wallet $wallet): bool
    {
        return false; // Staff cannot update wallets - read only
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(StaffAccount $user, Wallet $wallet): bool
    {
        return false; // Staff cannot delete wallets - read only
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(StaffAccount $user, Wallet $wallet): bool
    {
        return false; // Staff cannot restore wallets - read only
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(StaffAccount $user, Wallet $wallet): bool
    {
        return false; // Staff cannot force delete wallets - read only
    }
}