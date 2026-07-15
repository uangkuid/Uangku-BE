<?php

namespace App\Repositories\FamilyKey;

use App\Models\FamilyKey;
use LaravelEasyRepository\Implementations\Eloquent;

class FamilyKeyRepositoryImplement extends Eloquent implements FamilyKeyRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected FamilyKey $model;

    public function __construct(FamilyKey $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Get a family key by family ID
     */
    public function getFamilyKey(string $familyId): ?FamilyKey
    {
        return $this->model
            ->select('id', 'public_key', 'family')
            ->where('family', $familyId)
            ->first();
    }
}
