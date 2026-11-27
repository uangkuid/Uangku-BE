<?php

namespace App\Exceptions;

/**
 * The refresh token is genuine but belongs to somebody else.
 */
class SessionOwnershipException extends SessionException
{
    //
}
