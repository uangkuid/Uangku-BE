<?php

namespace App\Repositories\FamilyMember;

use LaravelEasyRepository\Repository;

interface FamilyMemberRepository extends Repository{

    /**
     * Check if the user is already a family
     * @param string $userId
     * @return bool
     */
    function isAlreadyFamily(string $userId): bool;
}
