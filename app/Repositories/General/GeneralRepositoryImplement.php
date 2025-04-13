<?php

namespace App\Repositories\General;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\General;

class GeneralRepositoryImplement extends Eloquent implements GeneralRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected General $model;

    public function __construct(General $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
