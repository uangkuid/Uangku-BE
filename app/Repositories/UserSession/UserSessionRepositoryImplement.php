<?php

namespace App\Repositories\UserSession;

use App\Models\UserSeasons;
use LaravelEasyRepository\Implementations\Eloquent;
use SensitiveParameter;

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
     * Callers pass the plaintext `refresh_token`; only its SHA-256 hash is
     * stored, so a DB leak does not hand out usable tokens.
     */
    public function create($data)
    {
        if (isset($data['refresh_token'])) {
            $data['refresh_token_hash'] = $this->hash($data['refresh_token']);
            unset($data['refresh_token']);
        }

        return $this->model->create($data);
    }

    /**
     * Check if the refresh token exists in the database.
     *
     * @param  string  $refreshToken
     */
    public function isRefreshTokenExist(#[SensitiveParameter] $refreshToken): bool
    {
        return $this->model->where('refresh_token_hash', $this->hash($refreshToken))->exists();
    }

    /**
     * Get the user session by refresh token and user ID.
     */
    public function getByUserIdAndRefreshToken(string $userId, #[SensitiveParameter] string $refreshToken): ?UserSeasons
    {
        return $this->model
            ->select('id', 'users', 'refresh_token_hash')
            ->where('users', $userId)
            ->where('refresh_token_hash', $this->hash($refreshToken))
            ->first();
    }

    /**
     * Atomically consume one session.
     */
    public function deleteByUserIdAndRefreshToken(string $userId, #[SensitiveParameter] string $refreshToken): bool
    {
        return $this->model
            ->where('users', $userId)
            ->where('refresh_token_hash', $this->hash($refreshToken))
            ->delete() > 0;
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

    private function hash(#[SensitiveParameter] string $refreshToken): string
    {
        return hash('sha256', $refreshToken);
    }
}
