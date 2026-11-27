<?php

namespace App\Repositories\StaffAccount;

use LaravelEasyRepository\Repository;

interface StaffAccountRepository extends Repository
{
    /**
     * Check if a staff account with the given name exists.
     */
    public function isNameExist(string $name): bool;
}
