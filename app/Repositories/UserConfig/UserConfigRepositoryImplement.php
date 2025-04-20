<?php

namespace App\Repositories\UserConfig;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\UserConfig;

class UserConfigRepositoryImplement extends Eloquent implements UserConfigRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected UserConfig $model;

    public function __construct(UserConfig $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * @param string $userId
     * @return ?UserConfig
     */
    public function getUserConfig(string $userId): ?UserConfig
    {
        return $this->model
            ->select('id', 'users', 'is_pin_enabled', 'start_date_month')
            ->where('users', $userId)
            ->first();
    }

    /**
     * @param string $userId
     * @param array $data
     * @return bool
     */
    public function updateUserConfig(string $userId, array $data): bool
    {
        $userConfig = $this->model
            ->select('id')
            ->where('users', $userId)
            ->first();

        if ($userConfig) {
            return $userConfig->update($data);
        }

        return false;
    }
}
