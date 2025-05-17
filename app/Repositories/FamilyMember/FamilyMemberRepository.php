<?php

namespace App\Repositories\FamilyMember;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface FamilyMemberRepository extends Repository{

    /**
     * Check if the user is already a family
     * @param string $userId
     * @return bool
     */
    function isAlreadyFamily(string $userId): bool;

    /**
     * Get a family member using Family id
     * @param string $familyId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getFamilyMember(string $familyId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get a family admin using family id
     * @param string $familyId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getFamilyAdmin(string $familyId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Check if the user has access to the family
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isHasAccess(string $userId, string $familyId): bool;

    /**
     * Check if the user has admin access to the family
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isHasAdmin(string $userId, string $familyId): bool;

    /**
     * Check if the user has joined the family before
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isHasJoinedBefore(string $userId, string $familyId): bool;

    /**
     * Grant admin access to a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function grantAdmin(string $userId, string $familyId): bool;

    /**
     * Grant access to a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function grantAccess(string $userId, string $familyId): bool;

    /**
     * Revoke member access from a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function revokeMember(string $userId, string $familyId): bool;

    /**
     * Check if the user is the owner of the family
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function isFamilyOwner(string $userId, string $familyId): bool;

    /**
     * Revoke admin access from a user
     * @param string $userId
     * @param string $familyId
     * @return bool
     */
    function revokeAdmin(string $userId, string $familyId): bool;
}
