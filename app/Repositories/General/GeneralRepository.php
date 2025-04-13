<?php

namespace App\Repositories\General;

use LaravelEasyRepository\Repository;

interface GeneralRepository extends Repository{

    function storeRedis($key, $value, $expire = 0);

    function getRedis($key): string;
    function deleteRedis($key);
}
