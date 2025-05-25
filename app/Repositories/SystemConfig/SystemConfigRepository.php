<?php

namespace App\Repositories\SystemConfig;

use LaravelEasyRepository\Repository;

interface SystemConfigRepository extends Repository{

    /**
     * Get the system configuration.
     * @return array
     */
    function getSystemConfig(): array;
}
