<?php

namespace App\Repositories\FamilyMember;

use App\Enums\FamilyMemberStatus;
use App\Enums\RoleFamily;
use App\Exceptions\GeneralException;
use App\Models\Wallet;
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
    protected Wallet $wallet;

    public function __construct(
        FamilyMember $model,
        Wallet $wallet
    ) {
        $this->model = $model;
        $this->wallet = $wallet;
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
    function getFamilyMemberPaging(string $familyId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select('id', 'user', 'family', 'role', 'created_at', 'updated_at')
            ->where('family', $familyId)
            ->with('users:id,email,avatar')
            ->where('status', FamilyMemberStatus::Active)
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
    function getFamilyAdminPaging(string $familyId, int $perPage = 10): LengthAwarePaginator
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

    /**
     * Leave from family
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function leaveFamily(string $userId, string $familyId): bool
    {
        return $this->model
            ->where('user', $userId)
            ->where('family', $familyId)
            ->update(['status' => FamilyMemberStatus::Left]);
    }

    /**
     * Get a family member using user id
     * @param string $userId
     * @return FamilyMember|null
     */
    function getDetailFromUser(string $userId): ?FamilyMember
    {
        return $this->model
            ->select('id', 'user', 'family', 'role', 'created_at', 'updated_at')
            ->where('user', $userId)
            ->first();
    }

    /**
     * Get a family member summary using family id
     * @param string $familyId
     * @return Collection
     */
    function getFamilyMemberSummary(string $familyId): Collection
    {
        return $this->model
            ->select('id', 'user', 'family', 'role', 'created_at', 'updated_at')
            ->where('family', $familyId)
            ->where('status', FamilyMemberStatus::Active)
            ->with('users:id,email,avatar')
            ->limit(5)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get a family member using wallet id
     * @param string $walletId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws GeneralException
     */
    function getMemberNotJoinWallet(string $walletId, int $perPage = 10): LengthAwarePaginator
    {
        // Validasi wallet existence + ambil family_id
        $wallet = $this->wallet->select('families')->find($walletId);

        if (!$wallet || !$wallet->families) {
            throw new GeneralException('Wallet not found on this family');
        }

        return $this->model
            ->select([
                'family_members.id',
                'family_members.user',
                'family_members.family',
                'family_members.role',
                'family_members.status',
                'family_members.created_at',
                'family_members.updated_at'
            ])
            ->leftJoin('wallet_accesses', function ($join) use ($walletId) {
                $join->on('wallet_accesses.users', '=', 'family_members.user')
                    ->where('wallet_accesses.wallets', '=', $walletId)
                    ->where('wallet_accesses.is_active', '=', true);
            })
            ->where('family_members.family', $wallet->families)
            ->where('family_members.status', 'active')
            ->whereNull('wallet_accesses.users')
            ->with('users:id,name,email,avatar')
            ->paginate($perPage);
    }
}
