<?php

namespace App\Repositories\User;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\UserKey;
use LaravelEasyRepository\Implementations\Eloquent;

class UserRepositoryImplement extends Eloquent implements UserRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected User $model;

    private UserKey $user_key;

    public function __construct(
        User $model,
        UserKey $user_key
    ) {
        $this->model = $model;
        $this->user_key = $user_key;
    }

    public function saveUserKey(string $userId, string $publicKey, string $privateKey, string $salt, int $iterations = EncryptionHelper::PBKDF2_ITERATIONS): UserKey
    {
        return $this->user_key->create([
            'users' => $userId,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'salt' => $salt,
            'iterations' => $iterations,
        ]);
    }

    public function isBlindIndexExist(string $blindIndex): bool
    {
        return $this->model
            ->select('id')
            ->where('blind_index', $blindIndex)->exists();
    }

    public function getUserKey(string $userId): ?UserKey
    {
        return $this->user_key
            ->where('users', $userId)->first();
    }

    public function getUserByBlindIndex(string $blindIndex): ?User
    {
        return $this->model
            ->where('blind_index', $blindIndex)->first();
    }
}
