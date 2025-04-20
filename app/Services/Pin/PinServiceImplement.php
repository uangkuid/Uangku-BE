<?php

namespace App\Services\Pin;

use App\Enums\OtpType;
use App\Exceptions\AuthException;
use App\Exceptions\PinException;
use App\Exceptions\SecurityException;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\UserKey;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\UserConfig\UserConfigRepository;
use Illuminate\Support\Facades\Log;
use LaravelEasyRepository\Service;
use App\Repositories\Pin\PinRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PinServiceImplement extends Service implements PinService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected PinRepository $mainRepository;
    private UserConfigRepository $userConfigRepository;
    private RedisRepository $redisRepository;
    private UserRepository $userRepository;

    public function __construct(
        PinRepository $mainRepository,
        UserConfigRepository $userConfigRepository,
        RedisRepository $redisRepository,
        UserRepository $userRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->userConfigRepository = $userConfigRepository;
        $this->redisRepository = $redisRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Check if pin is enabled for the user
     * @param string $token
     * @return bool
     * @throws AuthException
     */
    public function isPinEnable(string $token): bool
    {
        $user = JWTAuth::setToken($token)->toUser();
        $userConfig = $this->userConfigRepository->getUserConfig($user->id);

        if ($userConfig == null) {
            throw new AuthException("User Config not found");
        }

        return $userConfig->is_pin_enabled;
    }

    /**
     * Init PIN session for the user, active for 5 minutes and automatically deleted when expired
     * @param string $token
     * @return void
     * @throws SecurityException
     * @throws PinException
     */
    public function initPin(string $token): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $otpKey = OtpType::Pin;

        $email = EncryptionHelper::decryptFromString(
            encryptedData: $user->email,
            key: EncryptionHelper::getSystemSecretKey()
        );

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:$email");

        if ($isExist) {
            throw new PinException("PIN session already exist");
        }

        $this->redisRepository->storeRedis(
            key: "{$otpKey->value}:$email",
            value: json_encode([
                "email" => $email,
                "created_at" => now(),
            ]),
            expire: 300
        );
    }

    /**
     * @param string $token
     * @param string $pin
     * @param string $uuid
     * @param string $otp
     * @return void
     * @throws SecurityException|AuthException
     */
    public function createPin(string $token, string $pin, string $uuid, string $otp): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $otpKey = OtpType::Pin;
        $email = EncryptionHelper::decryptFromString(
            encryptedData: $user->email,
            key: EncryptionHelper::getSystemSecretKey()
        );

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:$email");

        if (!$isExist) {
            throw new AuthException("PIN session expired please try again!");
        }

        $otpData = json_decode($isExist, true);

        if ($otpData["email"] != $email) {
            throw new AuthException("Invalid OTP");
        }

        if ($otpData['uuid'] != $uuid) {
            throw new AuthException("Illegal OTP access!");
        }

        $this->redisRepository->deleteRedis("{$otpKey->value}:$email");

        $userConfig = $this->userConfigRepository->getUserConfig($user->id);
        $userKey = $this->userRepository->getUserKey($user->id);

        if ($userKey == null) {
            throw new AuthException("User Key not found");
        }

        if ($userConfig == null) {
            throw new AuthException("User Config not found");
        }

        $userKey->hashed_pin = EncryptionHelper::hashSecretKey($pin);
        $userKey->save();

        $userConfig->is_pin_enabled = true;
        $userConfig->save();
    }
}
