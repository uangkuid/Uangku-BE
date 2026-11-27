<?php

namespace App\Repositories\Otp;

use App\Models\Otp;
use LaravelEasyRepository\Implementations\Eloquent;
use Resend\Laravel\Facades\Resend;

class OtpRepositoryImplement extends Eloquent implements OtpRepository
{
    /**
     * Send OTP to the user using email
     *
     * @return void
     */
    public function sendEmail(string $email, string $subject, string $content)
    {
        $senderName = env('MAIL_FROM_NAME') ?? 'Uangku';
        $senderEmail = env('MAIL_FROM_ADDRESS') ?? 'oratakashi.com';

        Resend::emails()->send([
            'from' => "{$senderName} <{$senderEmail}>",
            'to' => [$email],
            'subject' => $subject,
            'html' => $content,
        ]);
    }
}
