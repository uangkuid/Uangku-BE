<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffAccountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaffAccount');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:StaffAccount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaffAccount');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:StaffAccount');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:StaffAccount');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaffAccount');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:StaffAccount');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:StaffAccount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaffAccount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaffAccount');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:StaffAccount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaffAccount');
    }

}