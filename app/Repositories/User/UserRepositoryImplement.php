<?php

namespace App\Repositories\User;

use App\Models\UserKey;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\User;

class UserRepositoryImplement extends Eloquent implements UserRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
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

    /**
     * Save the user's public and private keys.
     * @param string $userId
     * @param string $publicKey
     * @param string $privateKey
     * @return void
     */
    function saveUserKey(string $userId, string $publicKey, string $privateKey,) : UserKey
    {
        return $this->user_key->create([
            'users' => $userId,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
        ]);
    }

    /**
     * Check if the email already exists in the database.
     * @param string $email
     * @return bool
     */
    function isEmailExist(
        string $email,
    ): bool {
        return $this->model->where('email', $email)->count() > 0;
    }

    /**
     * Get the user's public and private keys.
     * @param string $userId
     * @return ?UserKey
     */
    function getUserKey(string $userId): ?UserKey
    {
        return $this->user_key->where('users', $userId)->first();
    }
}
