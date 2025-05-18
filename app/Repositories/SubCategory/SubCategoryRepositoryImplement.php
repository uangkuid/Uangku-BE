<?php

namespace App\Repositories\SubCategory;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\SubCategory;

class SubCategoryRepositoryImplement extends Eloquent implements SubCategoryRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected SubCategory $model;

    public function __construct(SubCategory $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
