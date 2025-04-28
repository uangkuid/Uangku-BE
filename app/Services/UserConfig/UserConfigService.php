<?php

namespace App\Services\UserConfig;

use App\Exceptions\EncryptionException;
use App\Exceptions\UserException;
use App\Models\UserConfig;
use LaravelEasyRepository\BaseService;

interface UserConfigService extends BaseService{
    /**
     * Get user config by user id
     * @param string $userId
     * @return UserConfig|null
     */
    function getConfigByUserId(string $userId): ?UserConfig;

    /**
     * Set a user income date
     * @param string $token
     * @param string $date
     * @return void
     * @throws UserException
     * @throws EncryptionException
     */
    function setDate(string $token, string $date): void;
}
