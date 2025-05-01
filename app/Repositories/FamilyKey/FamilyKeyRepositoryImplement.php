<?php

namespace App\Repositories\FamilyKey;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\FamilyKey;

class FamilyKeyRepositoryImplement extends Eloquent implements FamilyKeyRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected FamilyKey $model;

    public function __construct(FamilyKey $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
