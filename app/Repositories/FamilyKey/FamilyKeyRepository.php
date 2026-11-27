<?php

namespace App\Repositories\FamilyKey;

use App\Models\FamilyKey;
use LaravelEasyRepository\Repository;

interface FamilyKeyRepository extends Repository
{
    /**
     * Get a family key by family ID
     */
    public function getFamilyKey(string $familyId): ?FamilyKey;
}
