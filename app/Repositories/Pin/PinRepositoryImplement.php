<?php

namespace App\Repositories\Pin;

use App\Models\UserConfig;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Pin;

class PinRepositoryImplement extends Eloquent implements PinRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected UserConfig $model;

    public function __construct(UserConfig $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
