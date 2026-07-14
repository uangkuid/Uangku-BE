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
     */
    public function isPinEnable(string $token): bool;

    /**
     * Init PIN session for the user, active for 5 minutes and automatically deleted when expired
     *
     * @throws SecurityException
     *                           * @throws PinException
     */
    public function initPin(string $token): void;

    /**
     * Create Pin for the user and enable PIN for the user
     *
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
     *
     * @throws AuthException|SecurityException
     */
    public function deletePin(string $token): void;

    /**
     * Forgot Pin for the user, active for 5 minutes and automatically deleted when expired.
     * $authKey proves the caller currently holds valid password+secret-key
     * credentials (users.password stores bcrypt(authKey), not a raw password).
     *
     * @throws PinException|SecurityException|AuthException
     */
    public function forgotPin(
        string $token,
        string $authKey,
    ): void;

    /**
     * Reset Pin for the user
     */
    public function resetPin(
        string $token,
        string $pin,
        string $uuid,
        string $otp
    ): void;

    /**
     * Verify Pin for the user
     */
    public function verifyPin(
        string $token,
        string $pin,
    ): void;
}
