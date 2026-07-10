<?php

namespace App\Services\Staff;

use LaravelEasyRepository\BaseService;

interface StaffService extends BaseService{

    /**
     * Register a new staff account.
     * @param string $name
     * @param string $email
     * @param string $password
     * @return array
     */
    function register(
        string $name,
        string $email,
        string $password
    ): array;
}
