<?php

namespace App\Services\User;

use App\Enums\OtpType;
use App\Exceptions\AuthException;
use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use App\Exceptions\UserException;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\S3\S3Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use LaravelEasyRepository\Service;
use App\Repositories\User\UserRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Random\RandomException;

class UserServiceImplement extends Service implements UserService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected UserRepository $mainRepository;
    protected RedisRepository $redisRepository;
    protected S3Repository $s3Repository;

    public function __construct(
        UserRepository  $mainRepository,
        RedisRepository $redisRepository,
        S3Repository    $s3Repository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->redisRepository = $redisRepository;
        $this->s3Repository = $s3Repository;
    }


    /**
     * Get user by token
     *
     * @param string $token
     * @return User|null
     */
    function getUserByToken(string $token): ?User
    {
        return JWTAuth::setToken($token)->toUser();
    }

    /**
     * Pre-regenerate secret key for the user, active for 5 minutes and automatically deleted when expired
     *
     * @param string $token
     * @param string $password
     * @return void
     * @throws UserException|SecurityException
     *
     */
    function preRegenerateSecretKey(string $token, string $password): void
    {
        $otpKey = OtpType::GenerateSecretKey;
        $user = $this->getUserByToken($token);

        if (!$user) {
            throw new UserException("User not found");
        }

        if (!Hash::check($password, $user->password)) {
            throw new SecurityException("Invalid password");
        }

        $email = EncryptionHelper::decryptFromString(
            encryptedData: $user->email,
            key: EncryptionHelper::getSystemSecretKey()
        );

        $this->redisRepository->storeRedis("{$otpKey->value}:$email", json_encode([
            "email" => $email,
            "created_at" => now(),
        ]), (5 * 60)); // Store for 5 minutes
    }

    /**
     * Generate a new secret key for the user
     *
     * @param string $token
     * @param string $oldSecretKey
     * @param string $otp
     * @param string $uuid
     * @return string
     * @throws UserException|RandomException|SecurityException
     * @throws AuthException
     */
    function generateSecretKey(string $token, string $oldSecretKey, string $otp, string $uuid): string
    {
        $otpKey = OtpType::GenerateSecretKey;
        $user = $this->getUserByToken($token);


        if (!$user) {
            throw new UserException("User not found");
        }

        $email = EncryptionHelper::decryptFromString(
            encryptedData: $user->email,
            key: EncryptionHelper::getSystemSecretKey()
        );

        $otpData = json_decode($this->redisRepository->getRedis("{$otpKey->value}:{$email}"), true);

        if ($otpData === null) {
            throw new UserException("Session expired");
        }

        if ($otpData['otp'] != $otp) {
            throw new AuthException("Invalid OTP!");
        }

        if ($otpData['uuid'] != $uuid) {
            throw new AuthException("Illegal OTP access!");
        }

        // Generate a new secret key
        $newSecretKey = EncryptionHelper::generateUsersSecretKey();

        // Update the user's secret key in the database
        $this->mainRepository->update($user->id, ['secret_key' => EncryptionHelper::hashSecretKey($newSecretKey)]);

        // Delete the redis session
        $this->redisRepository->deleteRedis("{$otpKey->value}:$email");

        return $newSecretKey;
    }

    /**
     * Update user profile
     * @param string $token
     * @param string $name
     * @return void
     * @throws UserException
     * @throws EncryptionException
     */
    function updateProfile(string $token, string $name): void
    {
        $user = $this->getUserByToken($token);

        if (!$user) {
            throw new UserException("User not found");
        }

        $userKey = $this->mainRepository->getUserKey($user->id);

        if ($userKey === null) {
            throw new UserException("User Key not found");
        }

        $encryptedName = EncryptionHelper::encryptAsymmetric(
            data: $name,
            publicKey: base64_decode($userKey->public_key)
        );

        $user->name = $encryptedName;
        $user->save();
    }

    /**
     * Update user avatar
     * @param string $token
     * @param UploadedFile $avatar
     * @return string
     * @throws UserException
     */
    function updateAvatar(string $token, UploadedFile $avatar): string
    {
        $user = $this->getUserByToken($token);

        if (!$user) {
            throw new UserException("User not found");
        }

        $filename = uniqid() . '.' . $avatar->getClientOriginalExtension();

        $avatarUrl = $this->s3Repository->storeData(
            data: $avatar,
            fileName: $filename,
            path: "avatar/{$user->id}"
        );

        $user->avatar = $filename;

        $user->save();

        return $avatarUrl;
    }

    /**
     * Get user profile
     *
     * @param string $token
     * @return array
     * @throws UserException
     */
    function getProfile(string $token): array
    {
        $user = $this->getUserByToken($token);

        if (!$user) {
            throw new UserException("User not found");
        }

        $avatar = $this->s3Repository->getData("avatar/{$user->id}", $user->avatar);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $avatar,
        ];
    }
}
