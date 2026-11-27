<?php

namespace App\Repositories\FeatureStatus;

use App\Models\FeatureStatus;
use LaravelEasyRepository\Implementations\Eloquent;

class FeatureStatusRepositoryImplement extends Eloquent implements FeatureStatusRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected FeatureStatus $model;

    public function __construct(FeatureStatus $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
    public function getFeatureStatus(): array
    {
        return $this->model->all()->toArray();
    }
}
