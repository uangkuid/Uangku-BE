<?php

namespace App\Services\Staff;

use LaravelEasyRepository\BaseService;

interface StaffService extends BaseService
{
    /**
     * Register a new staff account.
     */
    public function register(
        string $name,
        string $email,
        string $password
    ): array;
}
