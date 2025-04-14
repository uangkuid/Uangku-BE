<?php

namespace App\Services\Otp;

use App\Exceptions\AuthException;
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
    function sendOtpRegister(
        string $email
    ): array;
}
