<?php

namespace App\Repositories\Redis;

use LaravelEasyRepository\Repository;

interface RedisRepository extends Repository{

    function storeRedis($key, $value, $expire = 0);

    function getRedis($key): ?string;
    function deleteRedis($key);
}
