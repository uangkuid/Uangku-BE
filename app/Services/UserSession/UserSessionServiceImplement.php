<?php

namespace App\Services\UserSession;

use App\Enums\TokenType;
use App\Enums\UserStatus;
use App\Exceptions\AuthException;
use App\Exceptions\SessionException;
use App\Exceptions\SessionOwnershipException;
use App\Helpers\TokenHelper;
use App\Models\User;
use App\Repositories\User\UserRepository;
use App\Repositories\UserSession\UserSessionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use SensitiveParameter;

class UserSessionServiceImplement extends Service implements UserSessionService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected UserSessionRepository $mainRepository;

    protected UserRepository $userRepository;

    public function __construct(
        UserSessionRepository $mainRepository,
        UserRepository $userRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->userRepository = $userRepository;
    }

    public function revokeSession(User $user, #[SensitiveParameter] string $refreshToken): void
    {
        $payload = TokenHelper::decode($refreshToken, TokenType::Refresh);

        if ($payload->get('sub') !== $user->id) {
            throw new SessionOwnershipException('Refresh token does not belong to this user');
        }

        if (
            TokenHelper::isBlacklisted($payload)
            || ! $this->mainRepository->deleteByUserIdAndRefreshToken($user->id, $refreshToken)
        ) {
            throw new SessionException('Invalid refresh token');
        }
    }

    public function rotateSession(
        #[SensitiveParameter] string $refreshToken,
        #[SensitiveParameter] ?string $accessToken = null
    ): array {
        $payload = TokenHelper::decode($refreshToken, TokenType::Refresh);
        $user = $this->userRepository->find($payload->get('sub'));

        if (! $user) {
            throw new SessionException('Invalid refresh token');
        }

        // Must run before the blacklist check: a rotated token is both
        // blacklisted and gone from the database.
        if (! $this->mainRepository->isRefreshTokenExist($refreshToken)) {
            $this->rejectReusedToken($user);
        }

        if (TokenHelper::isBlacklisted($payload)) {
            throw new SessionException('Invalid refresh token');
        }

        // This endpoint no longer sits behind auth:api, so EnsureUserIsActive
        // does not see the user unless a Bearer token is sent along.
        if ($user->status instanceof UserStatus && $user->status->isBlocked()) {
            $this->revokeAllSession($user);

            throw new AuthException('Akun Anda telah dinonaktifkan. Silakan hubungi dukungan.');
        }

        $tokens = DB::transaction(function () use ($user, $refreshToken) {
            if (! $this->mainRepository->deleteByUserIdAndRefreshToken($user->id, $refreshToken)) {
                return null;
            }

            $tokens = [
                'token' => JWTAuth::fromUser($user),
                'refresh_token' => TokenHelper::generateRefreshToken($user),
            ];

            $this->mainRepository->create([
                'users' => $user->id,
                'refresh_token' => $tokens['refresh_token'],
            ]);

            return $tokens;
        });

        // Outside the transaction, so the revocation is never rolled back.
        if ($tokens === null) {
            $this->rejectReusedToken($user);
        }

        TokenHelper::invalidate($refreshToken);
        $this->invalidateOwnAccessToken($user, $accessToken);

        return $tokens;
    }

    /**
     * @throws SessionException always
     */
    private function rejectReusedToken(User $user): never
    {
        Log::warning('Refresh token reuse detected, revoking all sessions', ['user_id' => $user->id]);

        $this->revokeAllSession($user);

        throw new SessionException('Invalid refresh token');
    }

    /**
     * Blacklist the Bearer token sent along with a refresh, if it is a valid
     * access token of the same user. Anything else is ignored.
     */
    private function invalidateOwnAccessToken(User $user, #[SensitiveParameter] ?string $accessToken): void
    {
        if (! $accessToken) {
            return;
        }

        try {
            $payload = TokenHelper::decode($accessToken, TokenType::Access);

            if ($payload->get('sub') === $user->id) {
                TokenHelper::invalidate($accessToken);
            }
        } catch (JWTException) {
            // Expired or garbage: nothing left to revoke.
        }
    }

    /**
     * Revoke all sessions for a user.
     *
     * @return void
     */
    public function revokeAllSession(User $user)
    {
        $this->mainRepository->deleteAllSession($user->id);
    }
}
