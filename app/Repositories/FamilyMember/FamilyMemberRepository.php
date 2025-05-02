<?php

namespace App\Repositories\FamilyMember;

use Illuminate\Database\Eloquent\Collection;
use LaravelEasyRepository\Repository;

interface FamilyMemberRepository extends Repository{

    /**
     * Check if the user is already a family
     * @param string $userId
     * @return bool
     */
    function isAlreadyFamily(string $userId): bool;

    /**
     * Get a family member using Family id
     * @param string $familyId
     * @return Collection
     */
    function getFamilyMember(string $familyId): Collection;
}
