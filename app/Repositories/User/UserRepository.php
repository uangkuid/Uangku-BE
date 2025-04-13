<?php

namespace App\Repositories\User;

use App\Models\UserKey;
use LaravelEasyRepository\Repository;

interface UserRepository extends Repository{

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
    ): UserKey;
}
