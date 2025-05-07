<?php

namespace App\Repositories\FamilyInvitation;

use App\Enums\InvitationStatus;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\FamilyInvitation;

class FamilyInvitationRepositoryImplement extends Eloquent implements FamilyInvitationRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected FamilyInvitation $model;

    public function __construct(FamilyInvitation $model)
    {
        $this->model = $model;
    }

    /**
     * Check if the family invitation exists for the given family and user IDs.
     * With status is pending or accepted
     * @param string $family_id
     * @param string $user_id
     * @return bool
     */
    function isExist(string $family_id, string $user_id): bool
    {
        return $this->model
            ->where('family', $family_id)
            ->where('invitee_id', $user_id)
            ->wherein('status', [
                InvitationStatus::Pending,
                InvitationStatus::Accepted
            ])
            ->exists();
    }
}
