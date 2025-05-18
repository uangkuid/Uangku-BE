<?php

namespace App\Repositories\Family;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Family;

class FamilyRepositoryImplement extends Eloquent implements FamilyRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Family $model;

    public function __construct(Family $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Get a family by its ID
     * @param string $familyId
     * @return Family|null
     */
    function getFamilyDetail(string $familyId): ?Family
    {
        return $this->model
            ->select('id', 'name', 'avatar', 'created_by')
            ->where('id', $familyId)->first();
    }
}
