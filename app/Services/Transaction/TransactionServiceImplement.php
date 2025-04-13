<?php

namespace App\Services\Transaction;

use LaravelEasyRepository\Service;
use App\Repositories\Transaction\TransactionRepository;

class TransactionServiceImplement extends Service implements TransactionService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected TransactionRepository $mainRepository;

    public function __construct(TransactionRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
