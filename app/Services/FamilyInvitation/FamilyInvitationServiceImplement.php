<?php

namespace App\Services\FamilyInvitation;

use LaravelEasyRepository\Service;
use App\Repositories\FamilyInvitation\FamilyInvitationRepository;

class FamilyInvitationServiceImplement extends Service implements FamilyInvitationService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected FamilyInvitationRepository $mainRepository;

    public function __construct(FamilyInvitationRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
}
