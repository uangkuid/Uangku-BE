<?php

namespace App\Repositories\UserSession;

use App\Models\UserSeasons;
use LaravelEasyRepository\Implementations\Eloquent;

class UserSessionRepositoryImplement extends Eloquent implements UserSessionRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected UserSeasons $model;

    public function __construct(UserSeasons $model)
    {
        $this->model = $model;
    }

    /**
     * Store only a SHA-256 hash of the refresh token — a DB leak no longer
     * hands out live, usable bearer tokens.
     */
    public function create($data)
    {
        if (isset($data['refresh_token'])) {
            $data['refresh_token'] = hash('sha256', $data['refresh_token']);
        }

        return $this->model->create($data);
    }

    /**
     * Check if the refresh token exists in the database.
     *
     * @param  string  $refreshToken
     */
    public function isRefreshTokenExist($refreshToken): bool
    {
        return $this->model->where('refresh_token', hash('sha256', $refreshToken))->exists();
    }

    /**
     * Get the user session by refresh token and user ID.
     */
    public function getByUserIdAndRefreshToken(string $userId, string $refreshToken): ?UserSeasons
    {
        return $this->model
            ->select('id', 'users', 'refresh_token')
            ->where('users', $userId)
            ->where('refresh_token', hash('sha256', $refreshToken))
            ->first();
    }

    /**
     * Delete all sessions for a user.
     */
    public function deleteAllSession(string $userId): bool
    {
        return $this->model
            ->where('users', $userId)
            ->delete();
    }
}
