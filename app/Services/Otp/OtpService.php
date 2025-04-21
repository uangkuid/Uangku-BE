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
     * @param string $email
     * @return array
     * @throws RandomException
     * @throws AuthException
     */
    function sendRegister(
        string $email
    ): array;

    /**
     * Send OTP to the user for changing password.
     * @param string $token
     * @return array
     * @throws RandomException
     * @throws AuthException
     * @throws Exception
     */
    function sendChangePassword(
        string $token
    ): array;

    /**
     * Send OTP to the user for a forgotten password.
     * @param string $email
     * @return array
     * @throws RandomException
     * @throws AuthException
     */
    function sendForgotPassword(
        string $email
    ): array;

    /**
     * Send OTP to the user for enable/disable PIN.
     * @param string|null $bearerToken
     * @return array
     * @throws SecurityException
     * @throws RandomException
     */
    function sendPin(?string $bearerToken): array;

    /**
     * Send OTP to the user for a forgotten PIN.
     * @param string $bearerToken
     * @return array
     */
    function sendForgotPin(string $bearerToken): array;
}
