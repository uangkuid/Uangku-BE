<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SystemConfig;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemConfigPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SystemConfig');
    }

    public function view(AuthUser $authUser, SystemConfig $systemConfig): bool
    {
        return $authUser->can('View:SystemConfig');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SystemConfig');
    }

    public function update(AuthUser $authUser, SystemConfig $systemConfig): bool
    {
        return $authUser->can('Update:SystemConfig');
    }

    public function delete(AuthUser $authUser, SystemConfig $systemConfig): bool
    {
        return $authUser->can('Delete:SystemConfig');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SystemConfig');
    }

    public function restore(AuthUser $authUser, SystemConfig $systemConfig): bool
    {
        return $authUser->can('Restore:SystemConfig');
    }

    public function forceDelete(AuthUser $authUser, SystemConfig $systemConfig): bool
    {
        return $authUser->can('ForceDelete:SystemConfig');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SystemConfig');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SystemConfig');
    }

    public function replicate(AuthUser $authUser, SystemConfig $systemConfig): bool
    {
        return $authUser->can('Replicate:SystemConfig');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SystemConfig');
    }

}