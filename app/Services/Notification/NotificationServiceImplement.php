<?php

namespace App\Services\Notification;

use LaravelEasyRepository\Service;
use App\Repositories\Notification\NotificationRepository;

class NotificationServiceImplement extends Service implements NotificationService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected NotificationRepository $mainRepository;

    public function __construct(NotificationRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
