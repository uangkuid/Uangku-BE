<?php

namespace App\Services\User;

use App\Exceptions\EncryptionException;
use App\Exceptions\UserException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use LaravelEasyRepository\BaseService;

interface UserService extends BaseService
{
    /**
     * Get user by token
     */
    public function getUserByToken(string $token): ?User;

    /**
     * Get user profile
     *
     * @throws UserException
     */
    public function getProfile(string $token): array;

    /**
     * Update user profile
     *
     * @throws UserException
     * @throws EncryptionException
     */
    public function updateProfile(
        string $token,
        string $name
    ): void;

    /**
     * Update user avatar
     */
    public function updateAvatar(
        string $token,
        UploadedFile $avatar
    ): string;
}
