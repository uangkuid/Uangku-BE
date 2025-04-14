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
     * @return User
     * @throws AuthException
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
     * Pre-register a new user. active for 5 minutes when expired user will delete automatically
     * @param string $email
     * @return void
     * @throws AuthException
     * @throws Exception
     */
    function preRegister(
        string $email
    );
}
