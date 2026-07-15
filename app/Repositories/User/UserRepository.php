<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\UserKey;
use LaravelEasyRepository\Repository;

interface UserRepository extends Repository
{
    /**
     * Save the user's public key and client-wrapped private key.
     *
     * @param  string  $publicKey  Plaintext (public by definition).
     * @param  string  $privateKey  Ciphertext: wrapped client-side with the 2SKD unlockKey.
     * @param  string  $salt  Base64 PBKDF2 salt used by the client to derive kdfPass.
     */
    public function saveUserKey(
        string $userId,
        string $publicKey,
        string $privateKey,
        string $salt
    ): UserKey;

    public function getUserKey(
        string $userId
    ): ?UserKey;

    /**
     * Check if a user with the given email blind index already exists.
     */
    public function isBlindIndexExist(string $blindIndex): bool;

    /**
     * Look up a user by their email blind index (HMAC lookup, not plaintext email).
     */
    public function getUserByBlindIndex(string $blindIndex): ?User;
}
