<?php

namespace App\Repositories\Family;

use App\Models\Family;
use LaravelEasyRepository\Repository;

interface FamilyRepository extends Repository{

    /**
     * Get a family by its ID
     * @param string $familyId
     * @return Family|null
     */
    function getFamilyDetail(string $familyId): ?Family;
}
