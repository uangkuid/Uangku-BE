<?php

namespace App\Repositories\Notification;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Notification;

class NotificationRepositoryImplement extends Eloquent implements NotificationRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Notification $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
