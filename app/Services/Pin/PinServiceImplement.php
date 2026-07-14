<?php

namespace App\Services\Pin;

use App\Enums\OtpType;
use App\Exceptions\AuthException;
use App\Exceptions\PinException;
use App\Exceptions\SecurityException;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Repositories\Pin\PinRepository;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\UserConfig\UserConfigRepository;
use Illuminate\Support\Facades\Hash;
use LaravelEasyRepository\Service;
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
     *
     * @throws AuthException
     */
    public function isPinEnable(string $token): bool
    {
        $user = JWTAuth::setToken($token)->toUser();
        $userConfig = $this->userConfigRepository->getUserConfig($user->id);

        if ($userConfig == null) {
            throw new AuthException('User Config not found');
        }

        return $userConfig->is_pin_enabled;
    }

    /**
     * Init PIN session for the user, active for 5 minutes and automatically deleted when expired
     *
     * @throws SecurityException
     * @throws PinException
     */
    public function initPin(string $token): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $otpKey = OtpType::Pin;

        $email = EncryptionHelper::decryptEmail($user->email);

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:$email");

        if ($isExist) {
            throw new PinException('PIN session already exist');
        }

        $this->redisRepository->storeRedis(
            key: "{$otpKey->value}:$email",
            value: json_encode([
                'email' => $email,
                'created_at' => now(),
            ]),
            expire: 300
        );
    }

    /**
     * @throws SecurityException|AuthException
     */
    public function createPin(string $token, string $pin, string $uuid, string $otp): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $otpKey = OtpType::Pin;
        $email = EncryptionHelper::decryptEmail($user->email);

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:$email");

        if (! $isExist) {
            throw new AuthException('PIN session expired please try again!');
        }

        $otpData = json_decode($isExist, true);

        if ($otpData['email'] != $email) {
            throw new AuthException('Invalid OTP');
        }

        if ($otpData['otp'] != $otp) {
            throw new AuthException('Invalid OTP');
        }

        if ($otpData['uuid'] != $uuid) {
            throw new AuthException('Illegal OTP access!');
        }

        $this->redisRepository->deleteRedis("{$otpKey->value}:$email");

        $userConfig = $this->userConfigRepository->getUserConfig($user->id);
        $userKey = $this->userRepository->getUserKey($user->id);

        if ($userKey == null) {
            throw new AuthException('User Key not found');
        }

        if ($userConfig == null) {
            throw new AuthException('User Config not found');
        }

        $userKey->hashed_pin = EncryptionHelper::hashSecret($pin);
        $userKey->save();

        $userConfig->is_pin_enabled = true;
        $userConfig->save();
    }

    /**
     * Delete Pin and disable PIN for the user
     *
     * @throws AuthException|SecurityException
     */
    public function deletePin(string $token): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $otpKey = OtpType::Pin;
        $email = EncryptionHelper::decryptEmail($user->email);

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:$email");

        if (! $isExist) {
            throw new AuthException('PIN session expired please try again!');
        }

        $userConfig = $this->userConfigRepository->getUserConfig($user->id);

        if ($userConfig == null) {
            throw new AuthException('User Config not found');
        }

        $userKey = $this->userRepository->getUserKey($user->id);

        if ($userKey == null) {
            throw new AuthException('User Key not found');
        }

        $userConfig->is_pin_enabled = false;
        $userConfig->save();

        $userKey->hashed_pin = null;
        $userKey->save();
    }

    /**
     * Forgot Pin for the user, active for 5 minutes and automatically deleted when expired.
     * $authKey proves the caller currently holds valid password+secret-key
     * credentials (users.password stores bcrypt(authKey), not a raw password).
     *
     * @throws PinException|SecurityException|AuthException
     */
    public function forgotPin(string $token, string $authKey): void
    {
        $user = JWTAuth::setToken($token)->toUser();
        $otpKey = OtpType::ForgotPin;
        $email = EncryptionHelper::decryptEmail($user->email);

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:$email");

        if ($isExist) {
            throw new PinException('Forgot PIN session already exist');
        }

        if (! Hash::check($authKey, $user->password)) {
            throw new AuthException('Invalid credentials');
        }

        $this->redisRepository->storeRedis(
            key: "{$otpKey->value}:$email",
            value: json_encode([
                'email' => $email,
                'created_at' => now(),
            ]),
            expire: 300
        );
    }

    /**
     * Reset Pin for the user
     *
     * @throws AuthException
     * @throws SecurityException
     */
    public function resetPin(string $token, string $pin, string $uuid, string $otp): void
    {
        $otpKey = OtpType::ForgotPin;
        $user = JWTAuth::setToken($token)->toUser();
        $email = EncryptionHelper::decryptEmail($user->email);
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:$email");

        if ($user == null) {
            throw new AuthException('User not found');
        }

        if (! $isExist) {
            throw new AuthException('Forgot PIN session expired please try again!');
        }

        $otpData = json_decode($isExist, true);

        if ($otpData['otp'] != $otp) {
            throw new AuthException('Invalid OTP!');
        }

        if ($otpData['uuid'] != $uuid) {
            throw new AuthException('Illegal OTP access!');
        }

        $this->redisRepository->deleteRedis("{$otpKey->value}:$email");

        $userKey = $this->userRepository->getUserKey($user->id);

        $userKey->hashed_pin = EncryptionHelper::hashSecret($pin);
        $userKey->save();
    }

    /**
     * Verify Pin for the user
     *
     * @throws AuthException
     */
    public function verifyPin(string $token, string $pin): void
    {
        $user = JWTAuth::setToken($token)->toUser();

        if ($user == null) {
            throw new AuthException('User not found');
        }

        $userKey = $this->userRepository->getUserKey($user->id);

        if ($userKey == null) {
            throw new AuthException('User Key not found');
        }

        $isValid = EncryptionHelper::validateSecret($pin, $userKey->hashed_pin);

        if (! $isValid) {
            throw new AuthException('Invalid PIN');
        }
    }
}
