<?php

namespace App\Repositories\FamilyMember;

use App\Exceptions\GeneralException;
use App\Models\FamilyMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface FamilyMemberRepository extends Repository
{
    /**
     * Check if the user is already a family
     */
    public function isAlreadyFamily(string $userId): bool;

    /**
     * Get a family member using Family id
     */
    public function getFamilyMemberPaging(string $familyId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get a family admin using family id
     */
    public function getFamilyAdminPaging(string $familyId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Check if the user has access to the family
     */
    public function isHasAccess(string $userId, string $familyId): bool;

    /**
     * Check if the user has admin access to the family
     */
    public function isHasAdmin(string $userId, string $familyId): bool;

    /**
     * Check if the user has joined the family before
     */
    public function isHasJoinedBefore(string $userId, string $familyId): bool;

    /**
     * Grant admin access to a user
     */
    public function grantAdmin(string $userId, string $familyId): bool;

    /**
     * Grant access to a user
     */
    public function grantAccess(string $userId, string $familyId): bool;

    /**
     * Revoke member access from a user
     */
    public function revokeMember(string $userId, string $familyId): bool;

    /**
     * Check if the user is the owner of the family
     */
    public function isFamilyOwner(string $userId, string $familyId): bool;

    /**
     * Revoke admin access from a user
     */
    public function revokeAdmin(string $userId, string $familyId): bool;

    /**
     * Leave from family
     */
    public function leaveFamily(string $userId, string $familyId): bool;

    /**
     * Get a family member using user id
     */
    public function getDetailFromUser(string $userId): ?FamilyMember;

    /**
     * Get a family member summary using family id
     */
    public function getFamilyMemberSummary(string $familyId): Collection;

    /**
     * Get a family member using wallet id
     *
     * @throws GeneralException
     */
    public function getMemberNotJoinWallet(string $walletId, int $perPage = 10): LengthAwarePaginator;
}
