<?php

namespace App\Repositories\General;

use App\Models\Category;
use Illuminate\Support\Facades\Redis;
use LaravelEasyRepository\Implementations\Eloquent;

class GeneralRepositoryImplement extends Eloquent implements GeneralRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Category $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    public function storeRedis($key, $value, $expire = 0)
    {
        Redis::command('set', [$key, $value, 'EX', 60]);
    }

    public function getRedis($key): string
    {
        return Redis::get($key);
    }

    public function deleteRedis($key)
    {
        Redis::del($key);
    }
}
