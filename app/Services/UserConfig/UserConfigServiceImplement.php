<?php

namespace App\Services\UserConfig;

use App\Exceptions\UserException;
use App\Models\UserConfig;
use App\Repositories\UserConfig\UserConfigRepository;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserConfigServiceImplement extends Service implements UserConfigService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected UserConfigRepository $mainRepository;

    public function __construct(
        UserConfigRepository $mainRepository
    ) {
        $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)

    /**
     * Get user config by user id
     */
    public function getConfigByUserId(string $userId): ?UserConfig
    {
        return $this->mainRepository->getUserConfig($userId);
    }

    /**
     * Set a user income date. The client has already encrypted $date to its
     * own public key (or the family's) — the server just stores the blob.
     *
     * @param  string  $date  Client ciphertext.
     *
     * @throws UserException
     */
    public function setDate(string $token, string $date): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $userConfig = $this->mainRepository->getUserConfig($user->id);

        if ($userConfig == null) {
            throw new UserException('User Config not found');
        }

        $userConfig->start_date_month = $date;

        $userConfig->save();
    }
}
