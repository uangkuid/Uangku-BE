<?php

namespace App\Helpers;

use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use Illuminate\Support\Facades\Hash;

/**
 * Server-side crypto surface for the Zero-Knowledge architecture.
 *
 * All key generation, key derivation (2SKD: password + secret key), and
 * encryption of financial data (wallet/transaction fields, private keys)
 * happens on the CLIENT. The server never receives a raw password, the
 * user's UANGKU-XXXX secret key, or a private key in plaintext.
 *
 * The only primitives that remain here are the ones the server legitimately
 * needs: verifying proof of the client-derived authKey, and encrypting the
 * deliberately-excluded fields (email, category names) with a server-held
 * key so support/admin tooling can read them.
 *
 * See docs/encryption.md for the full key hierarchy and the
 * client-side derivation contract clients must implement identically.
 */
class EncryptionHelper
{
    /** Version byte for the AES-256-GCM container: ver(1B)‖iv(12B)‖ciphertext(N)‖tag(16B), base64-encoded. */
    public const CIPHER_VERSION_GCM = 0x02;

    private const GCM_IV_LEN = 12;

    private const GCM_TAG_LEN = 16;

    /**
     * PBKDF2 iteration count clients must use to derive kdfPass in the 2SKD scheme.
     * Returned to clients via /auth/salt so it can be raised later without breaking old clients.
     */
    public const PBKDF2_ITERATIONS = 600000;

    /** HKDF `info` domain-separation labels for the 2SKD contract (docs/encryption.md §4.2). */
    public const INFO_SECRET_KEY = 'uangku-secretkey-v1';

    public const INFO_AUTH = 'uangku-auth-v1';

    // =========================================================================
    // AES-256-GCM (AEAD) — the only symmetric primitive used server-side.
    // =========================================================================

    /**
     * @throws EncryptionException
     */
    public static function aesGcmEncrypt(string $plaintext, string $key): string
    {
        if (strlen($key) !== 32) {
            throw new EncryptionException('AES-256-GCM requires a 32-byte key.');
        }

        $iv = random_bytes(self::GCM_IV_LEN);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::GCM_TAG_LEN);

        if ($ciphertext === false) {
            throw new EncryptionException('AES-256-GCM encryption failed.');
        }

        return base64_encode(chr(self::CIPHER_VERSION_GCM).$iv.$ciphertext.$tag);
    }

    /**
     * @throws SecurityException
     */
    public static function aesGcmDecrypt(string $blob, string $key): string
    {
        if (strlen($key) !== 32) {
            throw new SecurityException('AES-256-GCM requires a 32-byte key.');
        }

        $raw = base64_decode($blob, true);
        if ($raw === false || strlen($raw) < 1 + self::GCM_IV_LEN + self::GCM_TAG_LEN) {
            throw new SecurityException('Malformed ciphertext container.');
        }

        if (ord($raw[0]) !== self::CIPHER_VERSION_GCM) {
            throw new SecurityException('Unsupported ciphertext version.');
        }

        $iv = substr($raw, 1, self::GCM_IV_LEN);
        $tag = substr($raw, -self::GCM_TAG_LEN);
        $ciphertext = substr($raw, 1 + self::GCM_IV_LEN, -self::GCM_TAG_LEN);

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($plaintext === false) {
            throw new SecurityException('AES-256-GCM decryption/authentication failed.');
        }

        return $plaintext;
    }

    // =========================================================================
    // KDF primitives — exposed so the server can generate/verify the shared
    // test vectors clients (Vue/KMP) must reproduce byte-for-byte. Not used
    // to derive any key the server itself relies on for financial data.
    // =========================================================================

    public static function pbkdf2(string $password, string $salt, int $iterations = self::PBKDF2_ITERATIONS, int $length = 32): string
    {
        return hash_pbkdf2('sha256', $password, $salt, $iterations, $length, true);
    }

    public static function hkdf(string $ikm, string $info, int $length = 32, string $salt = ''): string
    {
        return hash_hkdf('sha256', $ikm, $length, $info, $salt);
    }

    // =========================================================================
    // Canonical 2SKD derivation (docs/encryption.md §4.2). This is the ONLY
    // place the derivation steps may be written — the seeder and the test
    // suite must call these functions, never re-implement the steps, or the
    // three copies can silently diverge (see faq-backend.md Blocker #1).
    // =========================================================================

    /**
     * @param  string  $rawSalt  16 raw bytes — already base64_decode()'d from user_keys.salt.
     *                           Used as BOTH the PBKDF2 salt and the HKDF salt for kdfSecret.
     */
    public static function deriveUnlockKey(string $password, string $secretKey, string $rawSalt, int $iterations = self::PBKDF2_ITERATIONS): string
    {
        $kdfPass = self::pbkdf2($password, $rawSalt, $iterations, 32);
        $kdfSecret = self::hkdf($secretKey, self::INFO_SECRET_KEY, 32, $rawSalt);

        return $kdfPass ^ $kdfSecret;
    }

    /** @param  string  $rawSalt  16 raw bytes — see deriveUnlockKey(). */
    public static function deriveAuthKey(string $password, string $secretKey, string $rawSalt, int $iterations = self::PBKDF2_ITERATIONS): string
    {
        return base64_encode(self::hkdf(
            self::deriveUnlockKey($password, $secretKey, $rawSalt, $iterations),
            self::INFO_AUTH,
            32
        ));
    }

    // =========================================================================
    // Email blind index + system-key encryption (the deliberate ZK exception).
    // Also reused for other admin-manageable, non-financial text such as
    // category/sub-category names.
    // =========================================================================

    /**
     * Deterministic HMAC-SHA256 lookup index for a system-decryptable value (email).
     * Deterministic-but-keyed avoids the static-IV equality leak of the old scheme
     * while still allowing O(1) lookups and uniqueness checks.
     *
     * @throws EncryptionException
     */
    public static function blindIndex(string $value): string
    {
        $key = env('MAIN_BLIND_INDEX_KEY');
        if (empty($key)) {
            throw new EncryptionException('MAIN_BLIND_INDEX_KEY is not configured.');
        }

        return hash_hmac('sha256', mb_strtolower(trim($value)), $key);
    }

    /**
     * Encrypt a system-readable value (email, category name) with the server-held
     * system key, AES-256-GCM with a random IV (no more static-IV equality leak).
     *
     * @throws EncryptionException
     */
    public static function encryptSystem(string $plaintext): string
    {
        return self::aesGcmEncrypt($plaintext, self::systemKey());
    }

    /**
     * @throws SecurityException
     * @throws EncryptionException
     */
    public static function decryptSystem(string $blob): string
    {
        return self::aesGcmDecrypt($blob, self::systemKey());
    }

    /** @throws EncryptionException */
    public static function encryptEmail(string $email): string
    {
        return self::encryptSystem($email);
    }

    /**
     * @throws SecurityException
     * @throws EncryptionException
     */
    public static function decryptEmail(string $blob): string
    {
        return self::decryptSystem($blob);
    }

    /**
     * @throws EncryptionException
     */
    private static function systemKey(): string
    {
        $key = env('MAIN_SYSTEM_KEY');
        if (empty($key)) {
            throw new EncryptionException('MAIN_SYSTEM_KEY is not configured.');
        }

        return hash('sha256', $key, true);
    }

    // =========================================================================
    // Generic secret hashing — used for the authKey verifier and the local PIN.
    // Both are bcrypt with a server-held pepper (MAIN_SALT_KEY); neither value
    // is reversible, and neither is the user's real password or secret key.
    // =========================================================================

    /**
     * @throws EncryptionException
     */
    public static function hashSecret(string $secret): string
    {
        return Hash::make(self::pepperedInput($secret));
    }

    /**
     * @throws EncryptionException
     */
    public static function validateSecret(string $secret, string $hashedSecret): bool
    {
        return Hash::check(self::pepperedInput($secret), $hashedSecret);
    }

    /**
     * HMAC-SHA256 the secret with the pepper first, so the bcrypt input is
     * always a fixed 44-char base64 string regardless of pepper length.
     * Without this, a MAIN_SALT_KEY of 72+ bytes silently truncates the
     * secret out of the bcrypt input entirely — see faq-backend.md Finding B.
     *
     * @throws EncryptionException
     */
    private static function pepperedInput(string $secret): string
    {
        $pepper = env('MAIN_SALT_KEY');
        if (empty($pepper)) {
            throw new EncryptionException('MAIN_SALT_KEY is not configured.');
        }

        return base64_encode(hash_hmac('sha256', $secret, $pepper, true));
    }
}
