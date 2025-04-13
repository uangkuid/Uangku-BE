<?php

namespace App\Services\Auth;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\UserKey;
use App\Repositories\User\UserRepository;
use Exception;
use LaravelEasyRepository\Service;

class AuthServiceImplement extends Service implements AuthService{

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected UserRepository $mainRepository;

    public function __construct(UserRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    /**
     * Register a new user.
     * @param string $name Encrypted name
     * @param string $email Encrypted email
     * @param string $password Raw password
     * @return User
     * @throws Exception
     */
    public function register(string $name, string $email, string $password): User
    {
        $user = User::where('email', $email);

        //Throw error when email already taken
        if ($user->count() > 0) {
            throw new Exception("Email already taken!");
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
}
