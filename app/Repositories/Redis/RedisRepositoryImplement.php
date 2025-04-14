<?php

namespace App\Repositories\Redis;

use Illuminate\Support\Facades\Redis;
use LaravelEasyRepository\Implementations\Eloquent;

class RedisRepositoryImplement extends Eloquent implements RedisRepository{

    public function storeRedis($key, $value, $expire = 0)
    {
        Redis::command('set', [$key, $value, 'EX', $expire]);
    }

    public function getRedis($key): ?string
    {
        return Redis::get($key);
    }

    public function deleteRedis($key)
    {
        Redis::del($key);
    }
}
