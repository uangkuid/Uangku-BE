<?php

namespace App\Policies;

use App\Models\StaffAccount;
use Illuminate\Auth\Access\Response;

class StaffAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(StaffAccount $user): bool
    {
        return $user->role === 'admin';
//        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(StaffAccount $user, StaffAccount $staffAccount): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(StaffAccount $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(StaffAccount $user, StaffAccount $staffAccount): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(StaffAccount $user, StaffAccount $staffAccount): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(StaffAccount $user, StaffAccount $staffAccount): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(StaffAccount $user, StaffAccount $staffAccount): bool
    {
        return false;
    }
}
