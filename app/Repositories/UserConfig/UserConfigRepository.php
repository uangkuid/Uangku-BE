<?php

namespace App\Repositories\UserConfig;

use App\Models\UserConfig;
use LaravelEasyRepository\Repository;

interface UserConfigRepository extends Repository{

    /**
     * @param string $userId
     * @return ?UserConfig
     */
    public function getUserConfig(string $userId): ?UserConfig;

    /**
     * @param string $userId
     * @param array $data
     * @return bool
     */
    public function updateUserConfig(string $userId, array $data): bool;
}
