<?php

namespace App\Services\Otp;

use App\Exceptions\AuthException;
use App\Repositories\Redis\RedisRepository;
use Illuminate\Support\Str;
use LaravelEasyRepository\Service;
use App\Repositories\Otp\OtpRepository;
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
     * Send OTP to the user for registration.
     * @param string $email
     * @return array
     * @throws RandomException
     * @throws AuthException
     */
    function sendOtpRegister(string $email): array
    {
        $isExist = $this->redisRepository->getRedis("pre-register:{$email}");

        /**
         * Check if email address not exist in redis throw error
         */
        if ($isExist == null) {
            throw new AuthException("Pre-register expired please try again!");
        }

        $otp = $this->generate();
        $uuid = Str::uuid()->toString();

        /**
         * Delete the pre-register email from redis and replace with otp value
         */
        $this->redisRepository->deleteRedis("pre-register:{$email}");

        $this->redisRepository->storeRedis("pre-register:{$email}", json_encode([
            'email' => $email,
            'otp' => $otp,
            'uuid' => $uuid,
        ]), $this->ttl);

        $html = view('emails.otp', ['otp' => $otp])->render();

        $this->mainRepository->sendEmail(
            email: $email,
            subject: "Uangku OTP Verification",
            content: $html,
        );

        return [
            'email' => $email,
            'uuid' => $uuid,
        ];
    }
}
