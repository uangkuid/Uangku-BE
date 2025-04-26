<?php

namespace App\Enums;

enum OtpType: string
{
    case Register = 'register';
    case ChangePassword = 'change-password';
    case ForgotPassword = 'forgot-password';
    case Pin = 'pin';
    case ForgotPin = 'forgot-pin';
    case GenerateSecretKey = "generate-secret-key";
}
