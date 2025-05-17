<?php

namespace App\Repositories\FamilyMember;

use App\Enums\FamilyMemberStatus;
use App\Enums\RoleFamily;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\FamilyMember;
use function Laravel\Prompts\select;

class FamilyMemberRepositoryImplement extends Eloquent implements FamilyMemberRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected FamilyMember $model;

    public function __construct(FamilyMember $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Check if the user is already a family
     * @param string $userId
     * @return bool
     */
    function isAlreadyFamily(string $userId): bool
    {
        return $this->model
            ->select('id')
            ->where('user', $userId)
            ->exists();
    }

    /**
     * Get a family member using Family id
     * @param string $familyId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getFamilyMember(string $familyId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select('id', 'user', 'family', 'role', 'created_at', 'updated_at')
            ->where('family', $familyId)
            ->with('users:id,email,avatar')
            ->paginate($perPage);
    }

    /**
     * Check if the user has access to the family
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isHasAccess(string $userId, string $familyId): bool
    {
        return $this->model
            ->select("id")
            ->where('user', $userId)
            ->where('family', $familyId)
            ->where('status', FamilyMemberStatus::Active)
            ->exists();
    }

    /**
     * Check if the user has admin access to the family
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isHasAdmin(string $userId, string $familyId): bool
    {
        return $this->model
            ->select('id')
            ->where('user', $userId)
            ->where('family', $familyId)
            ->whereIn('role', [RoleFamily::Admin->value, RoleFamily::Owner->value])
            ->exists();
    }

    /**
     * Check if the user has joined the family before
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isHasJoinedBefore(string $userId, string $familyId): bool {
        return $this->model
            ->select('id')
            ->where('user', $userId)
            ->where('family', $familyId)
            ->whereIn('status', [FamilyMemberStatus::Left, FamilyMemberStatus::Revoked])
            ->exists();
    }

    /**
     * Get a family admin using family id
     * @param string $familyId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getFamilyAdmin(string $familyId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select('id', 'user', 'family', 'role', 'created_at', 'updated_at')
            ->where('family', $familyId)
            ->whereIn('role', [RoleFamily::Admin->value, RoleFamily::Owner->value])
            ->with('users:id,email,avatar')
            ->paginate($perPage);
    }

    /**
     * Grant admin access to a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function grantAdmin(string $userId, string $familyId): bool
    {
        return $this->model
            ->where('user', $userId)
            ->where('family', $familyId)
            ->update(['role' => RoleFamily::Admin->value]);
    }


    /**
     * Grant access to a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function grantAccess(string $userId, string $familyId): bool
    {
        return $this->model
            ->where('user', $userId)
            ->where('family', $familyId)
            ->update(['status' => FamilyMemberStatus::Active]);
    }

    /**
     * Revoke member access from a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function revokeMember(string $userId, string $familyId): bool
    {
        return $this->model
            ->where('user', $userId)
            ->where('family', $familyId)
            ->update(['status' => FamilyMemberStatus::Revoked]);
    }

    /**
     * Check if the user is the owner of the family
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isFamilyOwner(string $userId, string $familyId): bool
    {
        return $this->model
            ->select('id')
            ->where('user', $userId)
            ->where('family', $familyId)
            ->where('role', RoleFamily::Owner->value)
            ->exists();
    }

    /**
     * Revoke admin access from a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function revokeAdmin(string $userId, string $familyId): bool
    {
        return $this->model
            ->where('user', $userId)
            ->where('family', $familyId)
            ->update(['role' => RoleFamily::Member->value]);
    }
}
