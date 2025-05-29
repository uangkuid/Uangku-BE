<?php

namespace App\Repositories\StaffAccount;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\StaffAccount;

class StaffAccountRepositoryImplement extends Eloquent implements StaffAccountRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected StaffAccount $model;

    public function __construct(StaffAccount $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Check if a staff account with the given name exists.
     * @param string $name
     * @return bool
     */
    function isNameExist(string $name): bool
    {
        return $this->model
            ->select('id')
            ->where('name', $name)
            ->exists();
    }
}
