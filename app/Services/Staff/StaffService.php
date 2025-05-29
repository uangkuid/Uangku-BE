<?php

namespace App\Services\Staff;

use LaravelEasyRepository\BaseService;

interface StaffService extends BaseService{

    /**
     * Register a new staff account.
     * @param string $name
     * @param string $email
     * @param string $password
     * @param bool $isSeeder
     * @return array
     */
    function register(
        string $name,
        string $email,
        string $password,
        bool $isSeeder = false
    ): array;
}
