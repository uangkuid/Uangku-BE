<?php

namespace App\Repositories\FamilyMember;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\FamilyMember;

class FamilyMemberRepositoryImplement extends Eloquent implements FamilyMemberRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected FamilyMember $model;

    public function __construct(FamilyMember $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Check if the user is already a family
     * @param string $userId
     * @return bool
     */
    function isAlreadyFamily(string $userId): bool
    {
        return $this->model
            ->where('user', $userId)
            ->exists();
    }
}
