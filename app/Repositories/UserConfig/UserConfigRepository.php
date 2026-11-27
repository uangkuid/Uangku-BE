<?php

namespace App\Repositories\UserConfig;

use App\Models\UserConfig;
use LaravelEasyRepository\Repository;

interface UserConfigRepository extends Repository
{
    public function getUserConfig(string $userId): ?UserConfig;

    public function updateUserConfig(string $userId, array $data): bool;
}
