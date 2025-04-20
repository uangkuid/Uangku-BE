<?php

namespace App\Services\Pin;

use App\Exceptions\PinException;
use App\Exceptions\SecurityException;
use App\Models\User;
use LaravelEasyRepository\BaseService;

interface PinService extends BaseService
{

    /**
     * Check if pin is enabled for the user
     * @param string $token
     * @return bool
     */
    public function isPinEnable(string $token): bool;

    /**
     * Init PIN session for the user, active for 5 minutes and automatically deleted when expired
     * @param string $token
     * @return void
     * @throws SecurityException
     * * @throws PinException
     */
    public function initPin(string $token): void;

    /**
     * @param string $token
     * @param string $pin
     * @param string $uuid
     * @param string $otp
     * @return void
     */
    public function createPin(
        string $token,
        string $pin,
        string $uuid,
        string $otp
    ): void;
}
