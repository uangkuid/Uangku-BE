<?php

namespace App\Services\UserConfig;

use App\Exceptions\EncryptionException;
use App\Exceptions\UserException;
use App\Helpers\EncryptionHelper;
use App\Models\UserConfig;
use App\Repositories\User\UserRepository;
use LaravelEasyRepository\Service;
use App\Repositories\UserConfig\UserConfigRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserConfigServiceImplement extends Service implements UserConfigService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected UserConfigRepository $mainRepository;
    private UserRepository $userRepository;

    public function __construct(
        UserConfigRepository $mainRepository,
        UserRepository $userRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->userRepository = $userRepository;
    }

    // Define your custom methods :)

    /**
     * Get user config by user id
     * @param string $userId
     * @return UserConfig|null
     */
    function getConfigByUserId(string $userId): ?UserConfig
    {
        return $this->mainRepository->getUserConfig($userId);
    }

    /**
     * Set a user income date
     * @param string $token
     * @param string $date
     * @return void
     * @throws UserException
     * @throws EncryptionException
     */
    function setDate(string $token, string $date): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $userKey = $this->userRepository->getUserKey($user->id);
        $userConfig = $this->mainRepository->getUserConfig($user->id);

        if ($userConfig == null) {
            throw new UserException("User Config not found");
        }

        $userConfig->start_date_month = EncryptionHelper::encryptAsymmetric(
            data: $date,
            publicKey: base64_decode($userKey->public_key)
        );

        $userConfig->save();
    }
}
