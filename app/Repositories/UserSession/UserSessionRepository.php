<?php

namespace App\Repositories\UserSession;

use LaravelEasyRepository\Repository;

interface UserSessionRepository extends Repository
{
    /**
     * Check if the refresh token exists in the database.
     */
    public function isRefreshTokenExist(string $refreshToken): bool;

    /**
     * Atomically consume one session. Returns false when it no longer exists,
     * e.g. because a concurrent request already rotated it.
     */
    public function deleteByUserIdAndRefreshToken(
        string $userId,
        string $refreshToken
    ): bool;

    /**
     * Delete all sessions for a user.
     */
    public function deleteAllSession(string $userId): bool;
}
