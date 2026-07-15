<?php

namespace Tests\Unit\Helpers;

use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use App\Helpers\EncryptionHelper;
use Tests\TestCase;

class EncryptionHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('MAIN_SALT_KEY=test_salt_key_67890');
        putenv('MAIN_SYSTEM_KEY=test_system_key_12345');
        putenv('MAIN_BLIND_INDEX_KEY=test_blind_index_key_abcde');
    }

    public function test_aes_gcm_encrypt_decrypt_round_trip(): void
    {
        $key = random_bytes(32);
        $plaintext = 'wallet balance payload';

        $ciphertext = EncryptionHelper::aesGcmEncrypt($plaintext, $key);
        $decrypted = EncryptionHelper::aesGcmDecrypt($ciphertext, $key);

        $this->assertNotEquals($plaintext, $ciphertext);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function test_aes_gcm_encrypt_produces_random_iv_each_time(): void
    {
        $key = random_bytes(32);
        $plaintext = 'same plaintext';

        $a = EncryptionHelper::aesGcmEncrypt($plaintext, $key);
        $b = EncryptionHelper::aesGcmEncrypt($plaintext, $key);

        // No static IV: identical plaintext must not produce identical ciphertext.
        $this->assertNotEquals($a, $b);
    }

    public function test_aes_gcm_decrypt_fails_on_tampered_ciphertext(): void
    {
        $key = random_bytes(32);
        $ciphertext = EncryptionHelper::aesGcmEncrypt('integrity check', $key);

        $raw = base64_decode($ciphertext);
        $raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 0xFF);
        $tampered = base64_encode($raw);

        $this->expectException(SecurityException::class);
        EncryptionHelper::aesGcmDecrypt($tampered, $key);
    }

    public function test_aes_gcm_decrypt_fails_with_wrong_key(): void
    {
        $ciphertext = EncryptionHelper::aesGcmEncrypt('secret', random_bytes(32));

        $this->expectException(SecurityException::class);
        EncryptionHelper::aesGcmDecrypt($ciphertext, random_bytes(32));
    }

    public function test_aes_gcm_rejects_non_32_byte_keys(): void
    {
        $this->expectException(EncryptionException::class);
        EncryptionHelper::aesGcmEncrypt('data', 'too-short-key');
    }

    public function test_pbkdf2_is_deterministic_for_same_inputs(): void
    {
        $salt = random_bytes(16);

        $a = EncryptionHelper::pbkdf2('correct horse battery staple', $salt, 10000, 32);
        $b = EncryptionHelper::pbkdf2('correct horse battery staple', $salt, 10000, 32);

        $this->assertEquals($a, $b);
        $this->assertEquals(32, strlen($a));
    }

    public function test_pbkdf2_differs_for_different_passwords(): void
    {
        $salt = random_bytes(16);

        $a = EncryptionHelper::pbkdf2('password-one', $salt, 10000);
        $b = EncryptionHelper::pbkdf2('password-two', $salt, 10000);

        $this->assertNotEquals($a, $b);
    }

    public function test_hkdf_domain_separation_produces_distinct_keys(): void
    {
        $ikm = random_bytes(32);

        $authKey = EncryptionHelper::hkdf($ikm, 'uangku-auth-v1');
        $encKey = EncryptionHelper::hkdf($ikm, 'uangku-enc-v1');

        $this->assertEquals(32, strlen($authKey));
        $this->assertNotEquals($authKey, $encKey);
    }

    /**
     * Simulates the client-side 2SKD derivation to prove the resulting
     * unlockKey requires BOTH the password and the secret key: flipping
     * either factor alone must change the derived key.
     */
    public function test_two_secret_key_derivation_requires_both_factors(): void
    {
        $salt = random_bytes(16);
        $deriveUnlockKey = function (string $password, string $secretKey) use ($salt): string {
            $kdfPass = EncryptionHelper::pbkdf2($password, $salt, 1000, 32);
            $kdfSecret = EncryptionHelper::hkdf($secretKey, 'uangku-secretkey-v1', 32, 'user-salt');

            return $kdfPass ^ $kdfSecret;
        };

        $correctPassword = 'CorrectHorse123!';
        $correctSecret = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';

        $reference = $deriveUnlockKey($correctPassword, $correctSecret);
        $wrongPassword = $deriveUnlockKey('WrongPassword!', $correctSecret);
        $wrongSecret = $deriveUnlockKey($correctPassword, 'UANGKU-000000-000000-00000-00000-00000');

        $this->assertNotEquals($reference, $wrongPassword);
        $this->assertNotEquals($reference, $wrongSecret);
        $this->assertNotEquals($wrongPassword, $wrongSecret);
    }

    public function test_hash_and_validate_secret_round_trip(): void
    {
        $authKey = base64_encode(random_bytes(32));
        $hashed = EncryptionHelper::hashSecret($authKey);

        $this->assertNotEquals($authKey, $hashed);
        $this->assertTrue(EncryptionHelper::validateSecret($authKey, $hashed));
        $this->assertFalse(EncryptionHelper::validateSecret('wrong-auth-key', $hashed));
    }

    public function test_blind_index_is_deterministic_and_case_insensitive(): void
    {
        $a = EncryptionHelper::blindIndex('User@Example.com');
        $b = EncryptionHelper::blindIndex('user@example.com ');

        $this->assertEquals($a, $b);
        $this->assertEquals(64, strlen($a)); // hex-encoded SHA-256
    }

    public function test_blind_index_differs_for_different_emails(): void
    {
        $a = EncryptionHelper::blindIndex('alice@example.com');
        $b = EncryptionHelper::blindIndex('bob@example.com');

        $this->assertNotEquals($a, $b);
    }

    public function test_encrypt_system_and_decrypt_system_round_trip(): void
    {
        $email = 'support-visible@example.com';
        $encrypted = EncryptionHelper::encryptSystem($email);
        $decrypted = EncryptionHelper::decryptSystem($encrypted);

        $this->assertNotEquals($email, $encrypted);
        $this->assertEquals($email, $decrypted);
    }

    public function test_encrypt_email_produces_different_ciphertext_each_time(): void
    {
        $email = 'same@example.com';

        $a = EncryptionHelper::encryptEmail($email);
        $b = EncryptionHelper::encryptEmail($email);

        // Random IV per encryption: no more static-IV equality leak.
        $this->assertNotEquals($a, $b);
        $this->assertEquals($email, EncryptionHelper::decryptEmail($a));
        $this->assertEquals($email, EncryptionHelper::decryptEmail($b));
    }

    public function test_encrypt_system_throws_when_key_not_configured(): void
    {
        $this->unsetEnv('MAIN_SYSTEM_KEY');

        $this->expectException(EncryptionException::class);
        EncryptionHelper::encryptSystem('data');
    }

    public function test_blind_index_throws_when_key_not_configured(): void
    {
        $this->unsetEnv('MAIN_BLIND_INDEX_KEY');

        $this->expectException(EncryptionException::class);
        EncryptionHelper::blindIndex('someone@example.com');
    }

    /**
     * putenv() alone doesn't clear $_ENV/$_SERVER, which phpunit.xml <env>
     * values populate — env() checks those first, so all three must be cleared.
     */
    private function unsetEnv(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}
