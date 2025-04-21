<?php

namespace App\Services\Otp;

use App\Enums\OtpType;
use App\Exceptions\AuthException;
use App\Exceptions\SecurityException;
use App\Helpers\EncryptionHelper;
use App\Repositories\Redis\RedisRepository;
use Exception;
use Illuminate\Support\Str;
use LaravelEasyRepository\Service;
use App\Repositories\Otp\OtpRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Random\RandomException;

class OtpServiceImplement extends Service implements OtpService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected OtpRepository $mainRepository;
    private RedisRepository $redisRepository;

    private int $ttl = 300; // 5 minutes

    /**
     * @param OtpRepository $mainRepository
     * @param RedisRepository $redisRepository
     */
    public function __construct(OtpRepository $mainRepository, RedisRepository $redisRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->redisRepository = $redisRepository;
    }

    /**
     * Generate a random OTP.
     * @return string generated OTP
     * @throws RandomException
     */
    private function generate(): string
    {
        $otp = random_int(100000, 999999);
        return (string)$otp;
    }

    /**
     * Send OTP to email address with given subject email
     * @param string $email
     * @param string $subject
     * @param OtpType $otpKey
     * @return array
     * @throws RandomException
     */
    private function sendEmail(
        string $email,
        string $subject,
        OtpType $otpKey
    ): array
    {
        $otp = $this->generate();
        $uuid = Str::uuid()->toString();

        /**
         * Delete the otp session email from redis and replace with otp value
         */
        $this->redisRepository->deleteRedis("{$otpKey->value}:{$email}");

        $this->redisRepository->storeRedis("{$otpKey->value}:{$email}", json_encode([
            'email' => $email,
            'otp' => $otp,
            'uuid' => $uuid,
        ]), $this->ttl);

        $html = view('emails.otp', ['otp' => $otp])->render();

        $this->mainRepository->sendEmail(
            email: $email,
            subject: "Uangku: $subject",
            content: $html,
        );

        return [
            'email' => $email,
            'uuid' => $uuid,
        ];
    }

    /**
     * Send OTP to the user for registration.
     * @param string $email
     * @return array
     * @throws RandomException
     * @throws AuthException
     */
    function sendRegister(string $email): array
    {
        $otpKey = OtpType::Register;
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        /**
         * Check if email address not exist in redis throw error
         */
        if ($isExist == null) {
            throw new AuthException("Pre-register expired please try again!");
        }

        return $this->sendEmail(
            email: $email,
            subject: "Register Verification",
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user for changing password.
     * @param string $token
     * @return array
     * @throws RandomException
     * @throws AuthException
     * @throws Exception
     */
    function sendChangePassword(string $token): array
    {
        $user = JWTAuth::setToken($token)->toUser();

        if ($user == null) {
            throw new AuthException("Invalid token");
        }

        $otpKey = OtpType::ChangePassword;

        $email = EncryptionHelper::decryptFromString(
            encryptedData: $user->email,
            key: EncryptionHelper::getSystemSecretKey()
        );

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        /**
         * Check if email address not exist in redis throw error
         */
        if ($isExist == null) {
            throw new AuthException("Change password session expired please try again!");
        }

        return $this->sendEmail(
            email: $email,
            subject: "Change Password Verification",
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user for forgot password.
     * @param string $email
     * @return array
     * @throws RandomException
     * @throws AuthException
     */
    function sendForgotPassword(string $email): array
    {
        $otpKey = OtpType::ForgotPassword;
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");
        /**
         * Check if email address not exist in redis throw error
         */
        if ($isExist == null) {
            throw new AuthException("Forgot Password expired please try again!");
        }

        return $this->sendEmail(
            email: $email,
            subject: "Forgot Password Verification",
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user for enable/disable PIN.
     * @param string|null $bearerToken
     * @return array
     * @throws SecurityException
     * @throws RandomException
     */
    function sendPin(?string $bearerToken): array
    {
        $otpKey = OtpType::Pin;
        $user = JWTAuth::setToken($bearerToken)->toUser();
        $email = EncryptionHelper::decryptFromString(
            encryptedData: $user->email,
            key: EncryptionHelper::getSystemSecretKey()
        );
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        /**
         * Check if email address not exists in redis throw error
         */
        if ($isExist == null) {
            throw new SecurityException("PIN session expired please try again!");
        }

        return $this->sendEmail(
            email: $email,
            subject: "PIN Verification",
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user for forgot PIN.
     * @param string $bearerToken
     * @return array
     * @throws RandomException
     * @throws SecurityException
     */
    function sendForgotPin(string $bearerToken): array
    {
        $otpKey = OtpType::ForgotPin;
        $user = JWTAuth::setToken($bearerToken)->toUser();
        $email = EncryptionHelper::decryptFromString(
            encryptedData: $user->email,
            key: EncryptionHelper::getSystemSecretKey()
        );

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");
        /**
         * Check if email address not exists in redis throw error
         */
        if ($isExist == null) {
            throw new SecurityException("Forgot PIN session expired please try again!");
        }

        return $this->sendEmail(
            email: $email,
            subject: "Forgot PIN Verification",
            otpKey: $otpKey,
        );
    }
}
