<?php

namespace App\Services\UserSession;

use App\Exceptions\AuthException;
use App\Exceptions\SessionException;
use LaravelEasyRepository\Service;
use App\Repositories\UserSession\UserSessionRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserSessionServiceImplement extends Service implements UserSessionService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected UserSessionRepository $mainRepository;

    public function __construct(UserSessionRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    /**
     * Revoke a user session by refresh token.
     * @param string $refreshToken
     * @throws SessionException
     */
    function revokeSession(string $refreshToken)
    {
        $isExist = $this->mainRepository->isRefreshTokenExist($refreshToken);

        if (!$isExist) {
            throw new SessionException("Invalid refresh token");
        }

        $user = JWTAuth::setToken($refreshToken)->toUser();

        if (!$user) {
            throw new SessionException("Invalid refresh token");
        }

        $userSession = $this->mainRepository->getByUserIdAndRefreshToken(
            $user->id,
            $refreshToken
        );

        if(!$userSession) {
            throw new SessionException("Invalid refresh token");
        }

        $userSession->delete();
    }
}
