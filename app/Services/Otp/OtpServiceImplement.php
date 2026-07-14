<?php

namespace App\Services\Otp;

use App\Enums\OtpType;
use App\Exceptions\AuthException;
use App\Exceptions\SecurityException;
use App\Helpers\EncryptionHelper;
use App\Repositories\Otp\OtpRepository;
use App\Repositories\Redis\RedisRepository;
use Exception;
use Illuminate\Support\Str;
use LaravelEasyRepository\Service;
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

    public function __construct(OtpRepository $mainRepository, RedisRepository $redisRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->redisRepository = $redisRepository;
    }

    /**
     * Generate a random OTP.
     *
     * @return string generated OTP
     *
     * @throws RandomException
     */
    private function generate(): string
    {
        $otp = random_int(100000, 999999);

        return (string) $otp;
    }

    /**
     * Send OTP to email address with given subject email
     *
     * @throws RandomException
     */
    private function sendEmail(
        string $email,
        string $subject,
        OtpType $otpKey
    ): array {
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
     *
     * @throws RandomException
     * @throws SecurityException
     */
    public function sendRegister(string $email): array
    {
        $otpKey = OtpType::Register;
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        /**
         * Check if email address not exist in redis throw error
         */
        if ($isExist == null) {
            throw new SecurityException('Pre-register expired please try again!');
        }

        return $this->sendEmail(
            email: $email,
            subject: 'Register Verification',
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user for changing password.
     *
     * @throws RandomException
     * @throws AuthException
     * @throws SecurityException
     * @throws Exception
     */
    public function sendChangePassword(string $token): array
    {
        $user = JWTAuth::setToken($token)->toUser();

        if ($user == null) {
            throw new AuthException('Invalid token');
        }

        $otpKey = OtpType::ChangePassword;

        $email = EncryptionHelper::decryptEmail($user->email);

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        /**
         * Check if email address not exist in redis throw error
         */
        if ($isExist == null) {
            throw new SecurityException('Change password session expired please try again!');
        }

        return $this->sendEmail(
            email: $email,
            subject: 'Change Password Verification',
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user for forgot password.
     *
     * @throws RandomException
     * @throws SecurityException
     */
    public function sendForgotPassword(string $email): array
    {
        $otpKey = OtpType::ForgotPassword;
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");
        /**
         * Check if email address not exist in redis throw error
         */
        if ($isExist == null) {
            throw new SecurityException('Forgot Password expired please try again!');
        }

        return $this->sendEmail(
            email: $email,
            subject: 'Forgot Password Verification',
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user to enable/disable PIN.
     *
     * @throws SecurityException
     * @throws RandomException
     * @throws AuthException
     */
    public function sendPin(?string $bearerToken): array
    {
        $otpKey = OtpType::Pin;
        $user = JWTAuth::setToken($bearerToken)->toUser();

        if ($user == null) {
            throw new AuthException('Invalid token');
        }

        $email = EncryptionHelper::decryptEmail($user->email);
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        /**
         * Check if email address not exists in redis throw error
         */
        if ($isExist == null) {
            throw new SecurityException('PIN session expired please try again!');
        }

        return $this->sendEmail(
            email: $email,
            subject: 'PIN Verification',
            otpKey: $otpKey,
        );
    }

    /**
     * Send OTP to the user for a forgotten PIN.
     *
     * @throws RandomException
     * @throws SecurityException
     * @throws AuthException
     */
    public function sendForgotPin(string $bearerToken): array
    {
        $otpKey = OtpType::ForgotPin;
        $user = JWTAuth::setToken($bearerToken)->toUser();

        if ($user == null) {
            throw new AuthException('Invalid token');
        }

        $email = EncryptionHelper::decryptEmail($user->email);

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");
        /**
         * Check if email address not exists in redis throw error
         */
        if ($isExist == null) {
            throw new SecurityException('Forgot PIN session expired please try again!');
        }

        return $this->sendEmail(
            email: $email,
            subject: 'Forgot PIN Verification',
            otpKey: $otpKey,
        );
    }
}
