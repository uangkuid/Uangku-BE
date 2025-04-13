<?php

namespace App\Services\Auth;
use App\Models\User;
use App\Models\UserKey;
use LaravelEasyRepository\BaseService;

interface AuthService extends BaseService{

    /**
     * Register a new user.
     * @param string $name
     * @param string $email
     * @param string $password
     * @return User
     */
    function register(
        string $name,
        string $email,
        string $password,
    ): User;

    /**
     * Save the user's public and private keys.
     * @param string $userId
     * @param string $publicKey
     * @param string $privateKey
     * @return void
     */
    function saveUserKey(
        string $userId,
        string $publicKey,
        string $privateKey,
        string $secretKey,
        string $password
    ): UserKey;
}
