<?php

namespace App\Repositories\UserSession;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\UserSeasons;

class UserSessionRepositoryImplement extends Eloquent implements UserSessionRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected UserSeasons $model;

    public function __construct(UserSeasons $model)
    {
        $this->model = $model;
    }

    /**
     * Check if the refresh token exists in the database.
     * @param string $refreshToken
     * @return bool
     */
    function isRefreshTokenExist($refreshToken): bool
    {
        return $this->model->where('refresh_token', $refreshToken)->exists();
    }

    /**
     * Get the user session by refresh token and user ID.
     * @param string $userId
     * @param string $refreshToken
     * @return UserSeasons|null
     */
    function getByUserIdAndRefreshToken(string $userId, string $refreshToken): ?UserSeasons
    {
        return $this->model
            ->select('id', 'users', 'refresh_token')
            ->where('users', $userId)
            ->where('refresh_token', $refreshToken)
            ->first();
    }


    /**
     * Delete all sessions for a user.
     * @param string $userId
     * @return bool
     */
    function deleteAllSession(string $userId): bool
    {
        return $this->model
            ->where('users', $userId)
            ->delete();
    }
}
