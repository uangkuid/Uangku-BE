<?php

namespace App\Services\Auth;

use App\Enums\OtpType;
use App\Enums\UserStatus;
use App\Exceptions\AuthException;
use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use App\Helpers\EncryptionHelper;
use App\Helpers\TokenHelper;
use App\Models\User;
use App\Models\UserKey;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\S3\S3Repository;
use App\Repositories\User\UserRepository;
use Exception;
use Illuminate\Support\Facades\Hash;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Random\RandomException;

class AuthServiceImplement extends Service implements AuthService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected UserRepository $mainRepository;

    protected RedisRepository $redisRepository;

    protected S3Repository $s3Repository;

    public function __construct(
        UserRepository $mainRepository,
        RedisRepository $redisRepository,
        S3Repository $s3Repository
    ) {
        $this->mainRepository = $mainRepository;
        $this->redisRepository = $redisRepository;
        $this->s3Repository = $s3Repository;
    }

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function register(
        string $name,
        string $email,
        string $authKey,
        string $salt,
        string $publicKey,
        string $wrappedPrivateKey,
        string $otp,
        string $uuid,
        bool $isSeeder = false
    ): array {
        $blindIndex = EncryptionHelper::blindIndex($email);
        $encryptedEmail = EncryptionHelper::encryptEmail($email);
        $otpKey = OtpType::Register;

        if (! $isSeeder) {
            $otpData = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

            if ($otpData == null) {
                throw new AuthException('Pre-register expired please try again!');
            }

            $otpData = json_decode($otpData, true);

            if ($otpData['otp'] != $otp) {
                throw new AuthException('Invalid OTP!');
            }

            if ($otpData['uuid'] != $uuid) {
                throw new AuthException('Illegal OTP access!');
            }

            $this->redisRepository->deleteRedis("{$otpKey->value}:{$email}");
        }

        if ($this->mainRepository->isBlindIndexExist($blindIndex)) {
            throw new AuthException('Email already taken!');
        }

        $user = $this->mainRepository->create([
            'name' => $name,
            'email' => $encryptedEmail,
            'blind_index' => $blindIndex,
            'password' => Hash::make($authKey),
            'email_verified_at' => now(),
        ]);

        $userKey = $this->mainRepository->saveUserKey(
            userId: $user->id,
            publicKey: $publicKey,
            privateKey: $wrappedPrivateKey,
            salt: $salt,
        );

        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = TokenHelper::generateRefreshToken($user);

        return [
            'user' => $user,
            'user_key' => $userKey,
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * @throws AuthException
     */
    public function getUserKey(string $userId): UserKey
    {
        $userKey = $this->mainRepository->getUserKey($userId);

        if ($userKey == null) {
            throw new AuthException('User key not found!');
        }

        return $userKey;
    }

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function preRegister(string $email): void
    {
        $blindIndex = EncryptionHelper::blindIndex($email);
        $otpKey = OtpType::Register;

        if ($this->mainRepository->isBlindIndexExist($blindIndex)) {
            throw new AuthException('Email already taken!');
        }

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        if ($isExist != null) {
            throw new AuthException('Email already taken!');
        }

        $this->redisRepository->storeRedis("{$otpKey->value}:{$email}", json_encode([
            'email' => $email,
            'created_at' => now(),
        ]), (5 * 60)); // Store for 5 minutes
    }

    /**
     * Return the salt a client needs to derive kdfPass. Unknown emails get a
     * deterministic decoy salt (HMAC of the email) so this endpoint can't be
     * used to enumerate registered accounts.
     */
    public function getSalt(string $email): array
    {
        $blindIndex = EncryptionHelper::blindIndex($email);
        $user = $this->mainRepository->getUserByBlindIndex($blindIndex);

        if ($user !== null) {
            $userKey = $this->mainRepository->getUserKey($user->id);
            $salt = $userKey?->salt;
        }

        if (empty($salt)) {
            // Deterministic per-email decoy so repeated lookups of the same
            // unknown email are indistinguishable from a real account.
            $salt = base64_encode(substr(hash_hmac('sha256', "decoy-salt:{$blindIndex}", env('MAIN_BLIND_INDEX_KEY', '')), 0, 16));
        }

        return [
            'salt' => $salt,
            'iterations' => EncryptionHelper::PBKDF2_ITERATIONS,
        ];
    }

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function login(string $email, string $authKey): array
    {
        $blindIndex = EncryptionHelper::blindIndex($email);
        $user = $this->mainRepository->getUserByBlindIndex($blindIndex);

        if ($user == null || ! Hash::check($authKey, $user->password)) {
            throw new AuthException('Wrong email or credentials!');
        }

        if ($user->status instanceof UserStatus && $user->status->isBlocked()) {
            throw new AuthException('Akun Anda telah dinonaktifkan. Silakan hubungi dukungan.');
        }

        $userKey = $this->mainRepository->getUserKey($user->id);

        if ($userKey == null) {
            throw new AuthException('User key not found!');
        }

        $token = JWTAuth::fromUser($user);
        $refreshToken = TokenHelper::generateRefreshToken($user);

        if ($user->avatar != null && $user->avatar != '') {
            $avatar = $this->s3Repository->getData("avatar/{$user->id}", $user->avatar);
        } else {
            $avatar = null;
        }

        return [
            'user' => $user,
            'user_key' => $userKey,
            'token' => $token,
            'refresh_token' => $refreshToken,
            'avatar' => $avatar,
        ];
    }

    /**
     * @throws AuthException
     */
    public function logout(string $token, string $refreshToken): bool
    {
        return JWTAuth::setToken($token)->invalidate() && JWTAuth::setToken($refreshToken)->invalidate();
    }

    /**
     * @throws AuthException
     * @throws Exception|SecurityException
     */
    public function preChangeCredentials($token): void
    {
        $user = JWTAuth::setToken($token)->toUser();

        if ($user == null) {
            throw new AuthException('Invalid token');
        }

        $otpKey = OtpType::ChangePassword;
        $email = EncryptionHelper::decryptEmail($user->email);

        $this->redisRepository->storeRedis("{$otpKey->value}:{$email}", json_encode([
            'email' => $email,
            'created_at' => now(),
        ]), (5 * 60));
    }

    /**
     * @throws AuthException|SecurityException
     */
    public function changeCredentials(
        string $token,
        string $oldAuthKey,
        string $newSalt,
        string $newAuthKey,
        string $newWrappedPrivateKey,
        string $otp,
        string $uuid
    ): User {
        $otpKey = OtpType::ChangePassword;
        $user = JWTAuth::setToken($token)->toUser();
        $email = EncryptionHelper::decryptEmail($user->email);

        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        if ($isExist == null) {
            throw new AuthException('Change credentials session expired please try again!');
        }

        if (! Hash::check($oldAuthKey, $user->password)) {
            throw new AuthException('Wrong current password or secret key!');
        }

        $otpData = json_decode($isExist, true);

        if ($otpData['otp'] != $otp) {
            throw new AuthException('Invalid OTP!');
        }

        if ($otpData['uuid'] != $uuid) {
            throw new AuthException('Illegal OTP access!');
        }

        $this->redisRepository->deleteRedis("{$otpKey->value}:{$email}");

        $userKey = $this->mainRepository->getUserKey($user->id);

        if ($userKey == null) {
            throw new AuthException('User key not found!');
        }

        $user->password = Hash::make($newAuthKey);
        $user->save();

        $userKey->salt = $newSalt;
        $userKey->private_key = $newWrappedPrivateKey;
        $userKey->save();

        JWTAuth::setToken($token)->invalidate();

        return $user;
    }

    /**
     * @throws AuthException
     * @throws EncryptionException|RandomException
     */
    public function forgotPassword(string $email): void
    {
        $blindIndex = EncryptionHelper::blindIndex($email);

        if (! $this->mainRepository->isBlindIndexExist($blindIndex)) {
            throw new AuthException('Email not found!');
        }

        $otpKey = OtpType::ForgotPassword;

        $this->redisRepository->storeRedis("{$otpKey->value}:{$email}", json_encode([
            'email' => $email,
            'created_at' => now(),
        ]), (5 * 60));
    }

    /**
     * @throws AuthException
     */
    public function resetCredentials(
        string $email,
        string $newSalt,
        string $newAuthKey,
        string $newPublicKey,
        string $newWrappedPrivateKey,
        string $otp,
        string $uuid
    ): User {
        $otpKey = OtpType::ForgotPassword;
        $isExist = $this->redisRepository->getRedis("{$otpKey->value}:{$email}");

        if ($isExist == null) {
            throw new AuthException('Reset password session expired please try again!');
        }

        $otpData = json_decode($isExist, true);

        if ($otpData['otp'] != $otp) {
            throw new AuthException('Invalid OTP!');
        }

        if ($otpData['uuid'] != $uuid) {
            throw new AuthException('Illegal OTP access!');
        }

        $this->redisRepository->deleteRedis("{$otpKey->value}:{$email}");

        $blindIndex = EncryptionHelper::blindIndex($email);
        $user = $this->mainRepository->getUserByBlindIndex($blindIndex);

        if ($user == null) {
            throw new AuthException('User not found!');
        }

        // The client cannot unwrap the old private key without the forgotten
        // password, so recovery necessarily replaces the key material with a
        // brand new keypair. Data encrypted under the old key is unreadable
        // afterwards — this is the same limitation every E2EE product has.
        $user->password = Hash::make($newAuthKey);
        $user->save();

        $userKey = $this->mainRepository->getUserKey($user->id);

        if ($userKey == null) {
            $this->mainRepository->saveUserKey(
                userId: $user->id,
                publicKey: $newPublicKey,
                privateKey: $newWrappedPrivateKey,
                salt: $newSalt,
            );
        } else {
            $userKey->public_key = $newPublicKey;
            $userKey->private_key = $newWrappedPrivateKey;
            $userKey->salt = $newSalt;
            $userKey->save();
        }

        return $user;
    }
}
