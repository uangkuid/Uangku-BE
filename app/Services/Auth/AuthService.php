<?php

namespace App\Services\Auth;

use App\Exceptions\AuthException;
use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\UserKey;
use Exception;
use LaravelEasyRepository\BaseService;
use Random\RandomException;

/**
 * Zero-Knowledge auth contract. The server never receives a raw password or
 * the user's UANGKU-XXXX secret key — only the client-derived authKey (proof
 * of both factors via 2SKD) and opaque, client-encrypted key material.
 * See docs/encryption.md for the full derivation clients must implement.
 */
interface AuthService extends BaseService
{
    /**
     * Register a new user. The client has already generated the secret key,
     * the RSA keypair, and wrapped the private key with the 2SKD unlockKey.
     *
     * @param  string  $authKey  Base64 authKey derived client-side from password + secret key.
     * @param  string  $salt  Base64 PBKDF2 salt used by the client.
     * @param  int  $iterations  PBKDF2 iterations the client used with $salt. Stored per-user
     *                           so raising the global default later doesn't lock out old accounts.
     * @param  string  $publicKey  Base64 public key (plaintext by definition).
     * @param  string  $wrappedPrivateKey  Client ciphertext (AES-256-GCM under unlockKey).
     *
     * @throws AuthException
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
        bool $isSeeder = false,
        int $iterations = EncryptionHelper::PBKDF2_ITERATIONS
    ): array;

    /**
     * @throws AuthException
     */
    public function getUserKey(string $userId): UserKey;

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function preRegister(string $email): void;

    /**
     * Return the salt (and KDF params) a client needs to derive kdfPass for the
     * given email. Returns a deterministic, indistinguishable salt for unknown
     * emails so the endpoint cannot be used to enumerate registered accounts.
     */
    public function getSalt(string $email): array;

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function login(string $email, string $authKey): array;

    /**
     * @throws AuthException
     */
    public function logout(string $token, string $refreshToken): bool;

    /**
     * @throws AuthException
     * @throws Exception|SecurityException
     */
    public function preChangeCredentials($token): void;

    /**
     * Change password/secret-key while authenticated: the client still holds
     * the old unlockKey, decrypts the existing private key, and re-wraps it
     * under the new unlockKey — no data loss.
     *
     * @throws AuthException|SecurityException
     */
    public function changeCredentials(
        string $token,
        string $oldAuthKey,
        string $newSalt,
        string $newAuthKey,
        string $newWrappedPrivateKey,
        string $otp,
        string $uuid,
        int $newIterations = EncryptionHelper::PBKDF2_ITERATIONS
    ): User;

    /**
     * @throws EncryptionException|RandomException
     * @throws AuthException
     */
    public function forgotPassword(string $email): void;

    /**
     * Recover account access after a forgotten password. Because the client
     * cannot unwrap the old private key without the old password, this
     * necessarily replaces the account's key material with a brand new
     * keypair — any data encrypted under the old key becomes unreadable.
     * This mirrors the real limitation of every E2EE product: the server
     * cannot recover what it never had the key to.
     *
     * @throws AuthException
     */
    public function resetCredentials(
        string $email,
        string $newSalt,
        string $newAuthKey,
        string $newPublicKey,
        string $newWrappedPrivateKey,
        string $otp,
        string $uuid,
        int $newIterations = EncryptionHelper::PBKDF2_ITERATIONS
    ): User;
}
