<?php

namespace App\Services\UserSession;

use App\Exceptions\AuthException;
use App\Exceptions\SessionException;
use App\Models\User;
use LaravelEasyRepository\BaseService;

interface UserSessionService extends BaseService
{

    /**
     * Revoke a user session by refresh token.
     * @param string $refreshToken
     * @return User : User revoked session
     * @throws SessionException
     */
    function revokeSession(string $refreshToken): User;

    /**
     * Revoke all sessions for a user.
     * @param User $user
     * @return void
     */
    function revokeAllSession(User $user);
}
