<?php

namespace App\Services\User;

use App\Exceptions\AuthException;
use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use App\Exceptions\UserException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use LaravelEasyRepository\BaseService;
use Random\RandomException;

interface UserService extends BaseService
{

    /**
     * Get user by token
     *
     * @param string $token
     * @return User|null
     */
    function getUserByToken(string $token): ?User;

    /**
     * Get user profile
     *
     * @param string $token
     * @return array
     * @throws UserException
     */
    function getProfile(string $token): array;

    /**
     * Pre-regenerate secret key for the user, active for 5 minutes and automatically deleted when expired
     *
     * @param string $token
     * @param string $password
     * @return void
     * @throws SecurityException|RandomException
     * @throws UserException
     *
     */
    function preRegenerateSecretKey(string $token, string $password): void;

    /**
     * Generate a new secret key for the user
     *
     * @param string $token
     * @param string $oldSecretKey
     * @param string $otp
     * @param string $uuid
     * @return string
     * @throws AuthException
     * @throws UserException
     * @throws RandomException
     * @throws SecurityException
     */
    function generateSecretKey(
        string $token,
        string $oldSecretKey,
        string $otp,
        string $uuid
    ): string;

    /**
     * Update user profile
     * @param string $token
     * @param string $name
     * @return void
     * @throws UserException
     * @throws EncryptionException
     */
    function updateProfile(
        string $token,
        string $name
    ): void;

    /**
     * Update user avatar
     * @param string $token
     * @param UploadedFile $avatar
     * @return string
     */
    function updateAvatar(
        string $token,
        UploadedFile $avatar
    ): string;
}
