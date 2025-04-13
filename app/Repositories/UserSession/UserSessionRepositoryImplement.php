<?php

namespace App\Repositories\UserSession;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\UserSession;

class UserSessionRepositoryImplement extends Eloquent implements UserSessionRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected UserSession $model;

    public function __construct(UserSession $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
