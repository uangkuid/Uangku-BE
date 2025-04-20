<?php

namespace App\Enums;

enum OtpType: string
{
    case Register = 'register';
    case ChangePassword = 'change-password';
    case ForgotPassword = 'forgot-password';
    case Pin = 'pin';
}
