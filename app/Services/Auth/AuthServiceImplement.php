<?php

namespace App\Services\Auth;

use LaravelEasyRepository\Service;
use App\Repositories\Auth\AuthRepository;

class AuthServiceImplement extends Service implements AuthService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected AuthRepository $mainRepository;

    public function __construct(AuthRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
