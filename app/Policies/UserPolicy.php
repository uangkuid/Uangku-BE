<?php

namespace App\Policies;

use App\Models\StaffAccount;

class UserPolicy
{
    /**
     * Determine whether the StaffAccount can view any models.
     */
    public function viewAny(StaffAccount $user): bool
    {
        return true;
    }

    /**
     * Determine whether the StaffAccount can view the model.
     */
    public function view(StaffAccount $user, StaffAccount $model): bool
    {
        return true;
    }

    /**
     * Determine whether the StaffAccount can create models.
     */
    public function create(StaffAccount $user): bool
    {
        return false;
    }

    /**
     * Determine whether the StaffAccount can update the model.
     */
    public function update(StaffAccount $user, StaffAccount $model): bool
    {
        return false;
    }

    /**
     * Determine whether the StaffAccount can delete the model.
     */
    public function delete(StaffAccount $user, StaffAccount $model): bool
    {
        return false;
    }

    /**
     * Determine whether the StaffAccount can restore the model.
     */
    public function restore(StaffAccount $user, StaffAccount $model): bool
    {
        return false;
    }

    /**
     * Determine whether the StaffAccount can permanently delete the model.
     */
    public function forceDelete(StaffAccount $user, StaffAccount $model): bool
    {
        return false;
    }
}
