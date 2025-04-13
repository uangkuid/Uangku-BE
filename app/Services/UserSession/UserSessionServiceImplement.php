<?php

namespace App\Services\UserSession;

use LaravelEasyRepository\Service;
use App\Repositories\UserSession\UserSessionRepository;

class UserSessionServiceImplement extends Service implements UserSessionService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected UserSessionRepository $mainRepository;

    public function __construct(UserSessionRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
