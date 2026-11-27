<?php

namespace App\Services\UserSession;

use App\Exceptions\AuthException;
use App\Exceptions\SessionException;
use App\Models\User;
use LaravelEasyRepository\BaseService;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use SensitiveParameter;

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
     * Exchange a refresh token for a new access + refresh token pair.
     *
     * The old session is removed and the old refresh token blacklisted. A
     * correctly signed refresh token that has no session left is treated as
     * stolen: every session of that user is revoked.
     *
     * @param  string|null  $accessToken  optional Bearer token, blacklisted too when it is a valid access token of the same user
     * @return array{token: string, refresh_token: string}
     *
     * @throws JWTException bad signature, expired, or not a refresh token
     * @throws SessionException unknown user, revoked or reused token
     * @throws AuthException the account is suspended or banned
     */
    public function rotateSession(
        #[SensitiveParameter] string $refreshToken,
        #[SensitiveParameter] ?string $accessToken = null
    ): array;

    /**
     * Revoke all sessions for a user.
     * @param User $user
     * @return void
     */
    function revokeAllSession(User $user);
}
