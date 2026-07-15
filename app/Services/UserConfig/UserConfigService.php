<?php

namespace App\Services\UserConfig;

use App\Exceptions\UserException;
use App\Models\UserConfig;
use LaravelEasyRepository\BaseService;

interface UserConfigService extends BaseService
{
    /**
     * Get user config by user id
     */
    public function getConfigByUserId(string $userId): ?UserConfig;

    /**
     * Set a user income date. $date is a client-encrypted ciphertext, stored as-is.
     *
     * @throws UserException
     */
    public function setDate(string $token, string $date): void;
}
