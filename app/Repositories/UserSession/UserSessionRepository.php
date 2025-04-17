<?php

namespace App\Repositories\UserSession;

use App\Models\UserSeasons;
use LaravelEasyRepository\Repository;

interface UserSessionRepository extends Repository{

    /**
     * Check if the refresh token exists in the database.
     * @param string $refreshToken
     * @return bool
     */
    function isRefreshTokenExist(string $refreshToken): bool;

    /**
     * Get the user session by refresh token and user ID.
     * @param string $userId
     * @param string $refreshToken
     * @return UserSeasons|null
     */
    function getByUserIdAndRefreshToken(
        string $userId,
        string $refreshToken
    ): ?UserSeasons;

    /**
     * Delete all sessions for a user.
     * @param string $userId
     * @return bool
     */
    function deleteAllSession(string $userId): bool;
}
