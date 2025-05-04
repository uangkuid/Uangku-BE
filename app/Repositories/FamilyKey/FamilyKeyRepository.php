<?php

namespace App\Repositories\FamilyKey;

use App\Models\FamilyKey;
use LaravelEasyRepository\Repository;

interface FamilyKeyRepository extends Repository{

    /**
     * Get a family key by family ID
     * @param string $familyId
     * @return FamilyKey|null
     */
    function getFamilyKey(string $familyId): ?FamilyKey;
}
