<?php

namespace App\Services\Auth;

use App\Exceptions\AuthException;
use App\Helpers\EncryptionHelper;
use App\Helpers\TokenHelper;
use App\Models\User;
use App\Models\UserKey;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\User\UserRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use LaravelEasyRepository\Service;

class AuthServiceImplement extends Service implements AuthService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected UserRepository $mainRepository;

    private RedisRepository $redisRepository;

    /**
     * @param UserRepository $mainRepository
     * @param RedisRepository $redisRepository
     */
    public function __construct(UserRepository $mainRepository, RedisRepository $redisRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->redisRepository = $redisRepository;
    }


    /**
     * Register a new user.
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $otp
     * @param string $uuid
     * @param bool $isSeeder
     * @return array
     * @throws AuthException
     * @throws Exception
     */
    public function register(
        string $name,
        string $email,
        string $password,
        string $otp,
        string $uuid,
        bool   $isSeeder = false
    ): array
    {
        /**
         * Prepare data before create account
         */
        $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
        $encryptedEmail = EncryptionHelper::encryptAsString(
            data: $email,
            key: EncryptionHelper::getSystemSecretKey(),
            iv: $staticIv,
        );
        $asymmetricKey = EncryptionHelper::generateAsymmetricKey();
        $rawPublicKey = base64_decode($asymmetricKey["public"]);
        $secretKey = EncryptionHelper::generateUsersSecretKey();

        /**
         * Skip checking OTP if seeder
         */
        if (!$isSeeder) {
            $otpData = $this->redisRepository->getRedis("pre-register:{$email}");

            Log::info("OTP Data: ", [
                "otpData" => $otpData,
                "email" => $email,
            ]);

            /**
             * Check if email address not exist in redis throw error
             */
            if ($otpData == null) {
                throw new AuthException("Pre-register expired please try again!");
            }

            $otpData = json_decode($otpData, true);

            if ($otpData['otp'] != $otp) {
                throw new AuthException("Invalid OTP!");
            }

            if ($otpData['uuid'] != $uuid) {
                throw new AuthException("Illegal OTP access!");
            }

            $this->redisRepository->deleteRedis("pre-register:{$email}");
        }

        $isExist = $this->mainRepository->isEmailExist($encryptedEmail);

        //Throw error when email already taken
        if ($isExist) {
            throw new AuthException("Email already taken!");
        }

        $user = $this->mainRepository->create([
            'name' => EncryptionHelper::encryptAsymmetric($name, $rawPublicKey),
            'email' => $encryptedEmail,
            'password' => bcrypt($password),
            'email_verified_at' => now(),
        ]);

        $accessToken = auth()->login($user);
        $refreshToken = TokenHelper::generateRefreshToken($user);

        return [
            "user" => $user,
            "public_key" => $asymmetricKey['public'],
            "private_key" => $asymmetricKey['private'],
            "raw_public_key" => $rawPublicKey,
            "secret_key" => $secretKey,
            "token" => $accessToken,
            "refresh_token" => $refreshToken,
        ];
    }

    /**
     * Save the user's public and private keys.
     * @param string $userId
     * @param string $publicKey
     * @param string $privateKey
     * @param string $secretKey
     * @param string $password
     * @return UserKey
     * @throws Exception
     */
    public function saveUserKey(string $userId, string $publicKey, string $privateKey, string $secretKey, string $password): UserKey
    {
        $encryptMasterKey = EncryptionHelper::getUsersEncryptKey($secretKey, $password);
        $encryptedPrivateKey = EncryptionHelper::encryptAsString($privateKey, $encryptMasterKey);

        return $this->mainRepository->saveUserKey(
            userId: $userId,
            publicKey: $publicKey,
            privateKey: $encryptedPrivateKey,
        );
    }

    /**
     * Get the user's public and private keys.
     * @param string $userId
     * @return UserKey
     * @throws AuthException
     */
    function getUserKey(string $userId): UserKey
    {
        $userKey = $this->mainRepository->getUserKey($userId);

        if ($userKey == null) {
            throw new AuthException("User key not found!");
        }

        return $userKey;
    }

    /**
     * Pre-register a new user. active for 5 minutes when expired user will delete automatically
     * @param string $email
     * @return void
     * @throws AuthException
     * @throws Exception
     */
    public function preRegister(string $email)
    {
        $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
        $isExist = $this->mainRepository->isEmailExist(EncryptionHelper::encryptAsString(
            data: $email,
            key: EncryptionHelper::getSystemSecretKey(),
            iv: $staticIv,
        ));

        if ($isExist) {
            throw new AuthException("Email already taken!");
        }

        $isExist = $this->redisRepository->getRedis("pre-register:{$email}");

        if ($isExist != null) {
            throw new AuthException("Email already taken!");
        }

        $this->redisRepository->storeRedis("pre-register:{$email}", json_encode([
            "email" => $email,
            "created_at" => now(),
        ]), (5 * 60)); // Store for 5 minutes
    }

    /**
     * Login a user.
     * @param string $email
     * @param string $password
     * @param string $secretKey
     * @return array
     * @throws AuthException
     * @throws Exception
     */
    function login(string $email, string $password, string $secretKey): array
    {
        /**
         * Prepare data before auth
         */
        $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");

        $encryptedEmail = EncryptionHelper::encryptAsString(
            data: $email,
            key: EncryptionHelper::getSystemSecretKey(),
            iv: $staticIv,
        );

        //get credentials from request
        $credentials = [
            'email' => $encryptedEmail,
            'password' => $password,
        ];

        //if auth failed
        if (!$token = auth()->guard('api')->attempt($credentials)) {
            throw new AuthException("Wrong email or password!");
        }

        $user = auth()->guard('api')->user();
        $refresh_token = TokenHelper::generateRefreshToken($user);

        return [
            "user" => $user,
            "token" => $token,
            "refresh_token" => $refresh_token,
        ];
    }
}
