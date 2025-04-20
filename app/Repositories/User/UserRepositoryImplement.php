<?php

namespace App\Repositories\User;

use App\Models\UserKey;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\User;

class UserRepositoryImplement extends Eloquent implements UserRepository
{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected User $model;

    private UserKey $user_key;

    public function __construct(
        User    $model,
        UserKey $user_key
    )
    {
        $this->model = $model;
        $this->user_key = $user_key;
    }

    /**
     * Save the user's public and private keys.
     * @param string $userId
     * @param string $publicKey
     * @param string $privateKey
     * @param string $hashedKey
     * * @return UserKey
     */
    function saveUserKey(string $userId, string $publicKey, string $privateKey, string $hashedKey): UserKey
    {
        return $this->user_key->create([
            'users' => $userId,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'hashed_key' => $hashedKey
        ]);
    }

    /**
     * Check if the email already exists in the database.
     * @param string $email
     * @return bool
     */
    function isEmailExist(
        string $email,
    ): bool
    {
        return $this->model
                ->select('email')
                ->where('email', $email)->count() > 0;
    }

    /**
     * Get the user's public and private keys.
     * @param string $userId
     * @return ?UserKey
     */
    function getUserKey(string $userId): ?UserKey
    {
        return $this->user_key
            ->where('users', $userId)->first();
    }

    /**
     * Get the user by email.
     * @param string $email
     * @return User|null
     */
    function getUserByEmail(string $email,): ?User
    {
        return $this->model
            ->select(
                'id',
                'name',
                'email',
                'password',
            )
            ->where('email', $email)->first();
    }
}
