<?php

namespace App\Services\Wallet;

use LaravelEasyRepository\Service;
use App\Repositories\Wallet\WalletRepository;

class WalletServiceImplement extends Service implements WalletService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected WalletRepository $mainRepository;

    public function __construct(WalletRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
