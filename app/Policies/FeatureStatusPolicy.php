<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FeatureStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeatureStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FeatureStatus');
    }

    public function view(AuthUser $authUser, FeatureStatus $featureStatus): bool
    {
        return $authUser->can('View:FeatureStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FeatureStatus');
    }

    public function update(AuthUser $authUser, FeatureStatus $featureStatus): bool
    {
        return $authUser->can('Update:FeatureStatus');
    }

    public function delete(AuthUser $authUser, FeatureStatus $featureStatus): bool
    {
        return $authUser->can('Delete:FeatureStatus');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FeatureStatus');
    }

    public function restore(AuthUser $authUser, FeatureStatus $featureStatus): bool
    {
        return $authUser->can('Restore:FeatureStatus');
    }

    public function forceDelete(AuthUser $authUser, FeatureStatus $featureStatus): bool
    {
        return $authUser->can('ForceDelete:FeatureStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FeatureStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FeatureStatus');
    }

    public function replicate(AuthUser $authUser, FeatureStatus $featureStatus): bool
    {
        return $authUser->can('Replicate:FeatureStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FeatureStatus');
    }

}