<?php

namespace App\Services\Family;

use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
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
    function getMember(string $id, int $perPage = 10): array;

    /**
     * Get a family admin list
     * @param string $id
     * @param int $perPage
     * @return array
     */
    function getAdmin(string $id, int $perPage = 10): array;

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
}
