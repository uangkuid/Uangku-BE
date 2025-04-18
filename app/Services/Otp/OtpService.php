<?php

namespace App\Services\Otp;

use App\Exceptions\AuthException;
use Exception;
use LaravelEasyRepository\BaseService;
use Random\RandomException;

interface OtpService extends BaseService{

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
     * Send OTP to the user for forgot password.
     * @param string $email
     * @return array
     * @throws RandomException
     * @throws AuthException
     */
    function sendForgotPassword(
        string $email
    ): array;
}
