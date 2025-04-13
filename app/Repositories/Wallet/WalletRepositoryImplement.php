<?php

namespace App\Repositories\Wallet;

use App\Models\WalletAccess;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Wallet;

class WalletRepositoryImplement extends Eloquent implements WalletRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Wallet $model;
    private WalletAccess $access;

    public function __construct(Wallet $model, WalletAccess $access){
        $this->model = $model;
        $this->access = $access;
    }
}
