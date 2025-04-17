<?php

namespace App\Enums;

enum OtpType: string
{
    case Register = 'register';
    case ChangePassword = 'change-password';
}
