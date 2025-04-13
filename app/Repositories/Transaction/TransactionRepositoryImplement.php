<?php

namespace App\Repositories\Transaction;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Transaction;

class TransactionRepositoryImplement extends Eloquent implements TransactionRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Transaction $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
