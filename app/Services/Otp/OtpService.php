<?php

namespace App\Services\Otp;

use App\Exceptions\AuthException;
use App\Exceptions\SecurityException;
use Exception;
use LaravelEasyRepository\BaseService;
use Random\RandomException;

interface OtpService extends BaseService
{
    /**
     * Send OTP to the user for registration.
     *
     * @throws RandomException
     * @throws AuthException
     */
    public function sendRegister(
        string $email
    ): array;

    /**
     * Send OTP to the user for changing password.
     *
     * @throws RandomException
     * @throws AuthException
     * @throws SecurityException
     * @throws Exception
     */
    public function sendChangePassword(
        string $token
    ): array;

    /**
     * Send OTP to the user for forgot password.
     *
     * @throws RandomException
     * @throws SecurityException
     */
    public function sendForgotPassword(
        string $email
    ): array;

    /**
     * Send OTP to the user to enable/disable PIN.
     *
     * @throws SecurityException
     * @throws RandomException
     * @throws AuthException
     */
    public function sendPin(?string $bearerToken): array;

    /**
     * Send OTP to the user for a forgotten PIN.
     *
     * @throws RandomException
     * @throws SecurityException
     * @throws AuthException
     */
    public function sendForgotPin(string $bearerToken): array;
}
