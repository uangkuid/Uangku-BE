<?php

namespace App\Repositories\UserKey;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\UserKey;

class UserKeyRepositoryImplement extends Eloquent implements UserKeyRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected UserKey $model;

    public function __construct(UserKey $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
