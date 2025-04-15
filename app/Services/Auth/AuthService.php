<?php

namespace App\Services\Auth;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Models\UserKey;
use Exception;
use LaravelEasyRepository\BaseService;

interface AuthService extends BaseService{

    /**
     * Register a new user.
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $otp
     * @param string $uuid
     * @param bool $isSeeder
     * @return array
     * @throws AuthException
     */
    function register(
        string $name,
        string $email,
        string $password,
        string $otp,
        string $uuid,
        bool $isSeeder = false
    ): array;

    /**
     * Save the user's public and private keys.
     * @param string $userId
     * @param string $publicKey
     * @param string $privateKey
     * @param string $secretKey
     * @param string $password
     * @return UserKey
     */
    function saveUserKey(
        string $userId,
        string $publicKey,
        string $privateKey,
        string $secretKey,
        string $password
    ): UserKey;

    /**
     * Get the user's public and private keys.
     * @param string $userId
     * @return UserKey
     * @throws AuthException
     */
    function getUserKey(
        string $userId
    ): UserKey;

    /**
     * Pre-register a new user. active for 5 minutes when expired user will delete automatically
     * @param string $email
     * @return void
     * @throws AuthException
     * @throws Exception
     */
    function preRegister(
        string $email
    );

    /**
     * Login a user.
     * @param string $email
     * @param string $password
     * @param string $secretKey
     * @throws Exception
     * @throws AuthException
     * @return array
     */
    function login(
        string $email,
        string $password,
        string $secretKey
    ): array;

    /**
     * Logout a user. and revoke the token
     * @param string $token
     * @param string $refreshToken
     * @return bool
     * @throws AuthException
     */
    function logout(
        string $token,
        string $refreshToken
    ): bool;
}
