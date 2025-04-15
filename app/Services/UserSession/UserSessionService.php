<?php

namespace App\Services\UserSession;

use App\Exceptions\AuthException;
use App\Exceptions\SessionException;
use LaravelEasyRepository\BaseService;

interface UserSessionService extends BaseService
{

    /**
     * Revoke a user session by refresh token.
     * @param string $refreshToken
     * @throws SessionException
     */
    function revokeSession(string $refreshToken);
}
