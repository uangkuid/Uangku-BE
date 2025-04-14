<?php

namespace App\Repositories\Otp;

use LaravelEasyRepository\Repository;

interface OtpRepository extends Repository{

    /**
     * Send OTP to the user using email
     * @param string $email
     * @param string $subject
     * @param string $content
     * @return void
     */
    function sendEmail(
        string $email,
        string $subject,
        string $content,
    );
}
