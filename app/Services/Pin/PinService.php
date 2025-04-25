<?php

namespace App\Services\Pin;

use App\Exceptions\AuthException;
use App\Exceptions\PinException;
use App\Exceptions\SecurityException;
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
     * Create Pin for the user and enable PIN for the user
     * @param string $token
     * @param string $pin
     * @param string $uuid
     * @param string $otp
     * @return void
     * @throws SecurityException|AuthException
     */
    public function createPin(
        string $token,
        string $pin,
        string $uuid,
        string $otp
    ): void;

    /**
     * Delete Pin and disable PIN for the user
     * @param string $token
     * @return void
     * @throws AuthException|SecurityException
     */
    public function deletePin(string $token): void;

    /**
     * Forgot Pin for the user, active for 5 minutes and automatically deleted when expired
     * @param string $token
     * @param string $password
     * @return void
     * @throws PinException|SecurityException|AuthException
     */
    public function forgotPin(
        string $token,
        string $password,
    ): void;

    /**
     * Reset Pin for the user
     * @param string $token
     * @param string $pin
     * @param string $uuid
     * @param string $otp
     * @return void
     */
    public function resetPin(
        string $token,
        string $pin,
        string $uuid,
        string $otp
    ): void;

    /**
     * Verify Pin for the user
     * @param string $token
     * @param string $pin
     * @return void
     */
    public function verifyPin(
        string $token,
        string $pin,
    ): void;
}
