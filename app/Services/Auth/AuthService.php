<?php

namespace App\Services\Auth;
use App\Models\User;
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
}
