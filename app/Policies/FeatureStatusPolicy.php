<?php

namespace App\Policies;

use App\Models\FeatureStatus;
use App\Models\StaffAccount;
use Illuminate\Auth\Access\Response;

class FeatureStatusPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(StaffAccount $user): bool
    {
        return true;
//        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(StaffAccount $user, FeatureStatus $featureStatus): bool
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
    public function update(StaffAccount $user, FeatureStatus $featureStatus): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(StaffAccount $user, FeatureStatus $featureStatus): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(StaffAccount $user, FeatureStatus $featureStatus): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(StaffAccount $user, FeatureStatus $featureStatus): bool
    {
        return $user->role === 'admin';
    }
}
