<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\UserKey;
use LaravelEasyRepository\Repository;

interface UserRepository extends Repository{

    /**
     * Save the user's public and private keys.
     * @param string $userId
     * @param string $publicKey
     * @param string $privateKey
     * @param string $hashedKey
     * @return UserKey
     */
    function saveUserKey(
        string $userId,
        string $publicKey,
        string $privateKey,
        string $hashedKey
    ): UserKey;

    /**
     * Get the user's public and private keys.
     * @param string $userId
     * @return ?UserKey
     */
    function getUserKey(
        string $userId
    ): ?UserKey;

    /**
     * Check if the email already exists in the database.
     * @param string $email
     * @return bool
     */
    function isEmailExist(
        string $email,
    ): bool;

    /**
     * Get the user by email.
     * @param string $email
     * @return User|null
     */
    function getUserByEmail(
        string $email,
    ): ?User;
}
