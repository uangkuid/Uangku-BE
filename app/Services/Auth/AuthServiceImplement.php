<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\User\UserRepository;
use LaravelEasyRepository\Service;

class AuthServiceImplement extends Service implements AuthService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected UserRepository $mainRepository;

    public function __construct(UserRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    public function register(string $name, string $email, string $password): User
    {
        $user = $this->mainRepository->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        return $user;
    }
}
