<?php

namespace App\Services\User;

use App\Models\User;
use LaravelEasyRepository\BaseService;

interface UserService extends BaseService{

    /**
     * Get user by token
     *
     * @param string $token
     * @return User|null
     */
    function getUserByToken(string $token): ?User;
}
