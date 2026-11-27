<?php

namespace App\Repositories\SystemConfig;

use App\Models\SystemConfig;
use LaravelEasyRepository\Implementations\Eloquent;

class SystemConfigRepositoryImplement extends Eloquent implements SystemConfigRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected SystemConfig $model;

    public function __construct(SystemConfig $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Get the system configuration.
     */
    public function getSystemConfig(): array
    {
        return $this->model->all()->toArray();
    }
}
