<?php

namespace App\Services\UserSession;

use App\Exceptions\AuthException;
use App\Exceptions\SessionException;
use App\Exceptions\SessionOwnershipException;
use App\Models\User;
use LaravelEasyRepository\BaseService;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use SensitiveParameter;

interface UserSessionService extends BaseService
{

    /**
     * Revoke one of the user's own sessions by refresh token.
     *
     * @param  User  $user  the user authenticated by the access token
     *
     * @throws JWTException bad signature, expired, or not a refresh token
     * @throws SessionOwnershipException the token belongs to another user; nothing is revoked
     * @throws SessionException revoked or unknown token
     */
    public function revokeSession(User $user, #[SensitiveParameter] string $refreshToken): void;

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
