<?php

namespace App\Services\User;

use App\Models\User;
use LaravelEasyRepository\Service;
use App\Repositories\User\UserRepository;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class UserServiceImplement extends Service implements UserService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected UserRepository $mainRepository;

    public function __construct(UserRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    /**
     * Get user by token
     *
     * @param string $token
     * @return User|null
     */
    function getUserByToken(string $token): ?User
    {
        return JWTAuth::setToken($token)->toUser();
    }
}
