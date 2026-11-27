<?php

namespace App\Repositories\Otp;

use LaravelEasyRepository\Repository;

interface OtpRepository extends Repository
{
    /**
     * Send OTP to the user using email
     *
     * @return void
     */
    public function sendEmail(
        string $email,
        string $subject,
        string $content,
    );
}
