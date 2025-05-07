<?php

namespace App\Repositories\FamilyInvitation;

use LaravelEasyRepository\Repository;

interface FamilyInvitationRepository extends Repository{
    /**
     * Check if the family invitation exists for the given family and user IDs.
     * With status is pending or accepted
     * @param string $family_id
     * @param string $user_id
     * @return bool
     */
    function isExist(string $family_id, string $user_id): bool;
}
