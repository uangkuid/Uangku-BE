<?php

namespace App\Services\Family;

use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LaravelEasyRepository\BaseService;
use Random\RandomException;

interface FamilyService extends BaseService{

    /**
     * Create a new Family
     * @param string $token
     * @param string $name
     * @return array
     * @throws RandomException
     * @throws EncryptionException
     * @throws FamilyException
     */
    function createFamily(string $token, string $name): array;

    /**
     * Get a family member list
     * @param string $id
     * @param int $perPage
     * @return array
     */
    function getMember(string $id, int $perPage = 10): AnonymousResourceCollection;

    /**
     * Get a family admin list
     * @param string $id
     * @param int $perPage
     * @return array
     */
    function getAdmin(string $id, int $perPage = 10): AnonymousResourceCollection;

    /**
     * Check if a user has access to a family
     * @param string $id
     * @param string $token
     * @return bool
     */
    function isHasAccess(string $id, string $token): bool;

    /**
     * Check if a user has admin access to a family
     * @param string $id
     * @param string $token
     * @return bool
     */
    function isHasAdminAccess(string $id, string $token): bool;

    /**
     * Validate a secret key
     * @param string $familyId
     * @param string $secretKey
     * @param string $token
     * @return array
     * @throws FamilyException
     */
    function validateSecretKey(string $familyId, string $secretKey, string $token): array;

    /**
     * Update a family data
     * @param string $familyId
     * @param string $name
     * @return void
     * @throws FamilyException
     * @throws EncryptionException
     */
    function updateFamily(string $familyId, string $name): void;

    /**
     * Invite a member of a family
     * @param string $familyId
     * @param string $token
     * @return array
     * @throws FamilyException
     */
    function inviteMember(string $familyId, string $token): array;

    /**
     * Response to an invitation
     * @param string $invitationId
     * @param string $familyId
     * @param string $token
     * @return array
     * @throws FamilyException
     */
    function responseInvitation(
        string $invitationId,
        string $familyId,
        string $token,
    ): array;

    /**
     * Grant admin access to a user
     * @param string $familyId
     * @param string $userId
     * @param string $token
     * @return void
     */
    function grantAdmin(
        string $familyId,
        string $userId,
        string $token,
    ): void;

    /**
     * Revoke member access to a family
     * @param string $familyId
     * @param string $userId
     * @param string $token
     * @return void
     * @throws FamilyException
     */
    function revokeMember(
        string $familyId,
        string $userId,
        string $token
    ): void;

    /**
     * Revoke admin access to a user
     * @param string $familyId
     * @param string $userId
     * @param string $token
     * @return void
     * @throws FamilyException
     */
    function revokeAdmin(
        string $familyId,
        string $userId,
        string $token
    ): void;
}
