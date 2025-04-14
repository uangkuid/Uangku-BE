<?php

namespace App\Services\Auth;

use App\Exceptions\AuthException;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\UserKey;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\User\UserRepository;
use Exception;
use LaravelEasyRepository\Service;

class AuthServiceImplement extends Service implements AuthService{

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
     * @param string $name Encrypted name
     * @param string $email Encrypted email
     * @param string $password Raw password
     * @return User
     * @throws AuthException
     */
    public function register(string $name, string $email, string $password): User
    {
        $isExist = $this->mainRepository->isEmailExist($email);

        //Throw error when email already taken
        if ($isExist) {
            throw new AuthException("Email already taken!");
        }

        $user = $this->mainRepository->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        return $user;
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
}
