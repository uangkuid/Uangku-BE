<?php

namespace App\Enums;

/**
 * Value of the `typ` claim carried by every JWT this API issues.
 */
enum TokenType: string
{
    case Access = 'access';
    case Refresh = 'refresh';
}
