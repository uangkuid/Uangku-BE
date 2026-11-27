<?php

namespace App\Repositories\Family;

use App\Models\Family;
use LaravelEasyRepository\Repository;

interface FamilyRepository extends Repository
{
    /**
     * Get a family by its ID
     */
    public function getFamilyDetail(string $familyId): ?Family;
}
