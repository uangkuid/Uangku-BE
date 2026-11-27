<?php

namespace App\Repositories\Redis;

use LaravelEasyRepository\Repository;

interface RedisRepository extends Repository
{
    public function storeRedis($key, $value, $expire = 0);

    public function getRedis($key): ?string;

    public function deleteRedis($key);
}
