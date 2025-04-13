<?php

namespace App\Repositories\General;

use App\Models\Category;
use LaravelEasyRepository\Implementations\Eloquent;

class GeneralRepositoryImplement extends Eloquent implements GeneralRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Category $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
