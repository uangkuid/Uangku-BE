<?php

namespace App\Services\UserConfig;

use LaravelEasyRepository\Service;
use App\Repositories\UserConfig\UserConfigRepository;

class UserConfigServiceImplement extends Service implements UserConfigService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected UserConfigRepository $mainRepository;

    public function __construct(UserConfigRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
