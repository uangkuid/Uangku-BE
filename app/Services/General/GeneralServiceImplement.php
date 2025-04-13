<?php

namespace App\Services\General;

use LaravelEasyRepository\Service;
use App\Repositories\General\GeneralRepository;

class GeneralServiceImplement extends Service implements GeneralService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected GeneralRepository $mainRepository;

    public function __construct(GeneralRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
