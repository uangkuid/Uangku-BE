<?php

namespace App\Repositories\StaffAccount;

use LaravelEasyRepository\Repository;

interface StaffAccountRepository extends Repository{

    /**
     * Check if a staff account with the given name exists.
     * @param string $name
     * @return bool
     */
    function isNameExist(string $name): bool;
}
