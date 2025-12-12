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
        // Set environment variables for testing
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
        putenv('MAIN_SECRET_KEY=test_secret_key_12345');
        putenv('MAIN_SALT_KEY=test_salt_key_67890');
    }

    public function test_encrypt_returns_array_with_iv_and_data(): void
    {
        $data = 'test data';
        $result = EncryptionHelper::encrypt($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('iv', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['iv']);
        $this->assertNotEmpty($result['data']);
    }

    public function test_encrypt_and_decrypt_symmetric(): void
    {
        $data = 'test data for encryption';
        $encrypted = EncryptionHelper::encrypt($data);

        $decrypted = EncryptionHelper::decrypt($encrypted['data'], $encrypted['iv']);

        $this->assertEquals($data, $decrypted);
    }

    public function test_encrypt_as_string_returns_string_with_dot_separator(): void
    {
        $data = 'test data';
        $result = EncryptionHelper::encryptAsString($data);

        $this->assertIsString($result);
        $this->assertStringContainsString('.', $result);
        $parts = explode('.', $result);
        $this->assertCount(2, $parts);
    }

    public function test_encrypt_as_string_and_decrypt_from_string(): void
    {
        $data = 'test data for encryption';
        $encrypted = EncryptionHelper::encryptAsString($data);

        $decrypted = EncryptionHelper::decryptFromString($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    public function test_encrypt_asymmetric_and_decrypt_asymmetric(): void
    {
        $keyPair = EncryptionHelper::generateAsymmetricKey();
        $publicKey = base64_decode($keyPair['public']);
        $privateKey = base64_decode($keyPair['private']);

        $data = 'test asymmetric encryption';
        $encrypted = EncryptionHelper::encryptAsymmetric($data, $publicKey);

        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);

        $decrypted = EncryptionHelper::decryptAsymmetric($encrypted, $privateKey);

        $this->assertEquals($data, $decrypted);
    }

    public function test_hash_secret_key_returns_hashed_string(): void
    {
        $secretKey = 'my-secret-key';
        $hashed = EncryptionHelper::hashSecretKey($secretKey);

        $this->assertIsString($hashed);
        $this->assertNotEquals($secretKey, $hashed);
        $this->assertNotEmpty($hashed);
    }

    public function test_validate_secret_key_with_correct_key(): void
    {
        $secretKey = 'my-secret-key';
        $hashed = EncryptionHelper::hashSecretKey($secretKey);

        $isValid = EncryptionHelper::validateSecretKey($secretKey, $hashed);

        $this->assertTrue($isValid);
    }

    public function test_validate_secret_key_with_incorrect_key(): void
    {
        $secretKey = 'my-secret-key';
        $wrongKey = 'wrong-secret-key';
        $hashed = EncryptionHelper::hashSecretKey($secretKey);

        $isValid = EncryptionHelper::validateSecretKey($wrongKey, $hashed);

        $this->assertFalse($isValid);
    }

    public function test_generate_users_secret_key_format(): void
    {
        $secretKey = EncryptionHelper::generateUsersSecretKey();

        $this->assertIsString($secretKey);
        $this->assertStringStartsWith('UANGKU-', $secretKey);

        // Check format: UANGKU-XXXXXX-XXXXXX-XXXXX-XXXXX-XXXXX
        $parts = explode('-', $secretKey);
        $this->assertCount(6, $parts);
        $this->assertEquals('UANGKU', $parts[0]);
        $this->assertEquals(6, strlen($parts[1]));
        $this->assertEquals(6, strlen($parts[2]));
        $this->assertEquals(5, strlen($parts[3]));
        $this->assertEquals(5, strlen($parts[4]));
        $this->assertEquals(5, strlen($parts[5]));
    }

    public function test_xor_string_produces_different_output(): void
    {
        $input = 'test string';
        $key = 16;

        $result = EncryptionHelper::xorString($input, $key);

        $this->assertIsString($result);
        $this->assertNotEquals($input, $result);
        $this->assertEquals(strlen($input), strlen($result));
    }

    public function test_xor_string_is_reversible(): void
    {
        $input = 'test string';
        $key = 16;

        $encoded = EncryptionHelper::xorString($input, $key);
        $decoded = EncryptionHelper::xorString($encoded, $key);

        $this->assertEquals($input, $decoded);
    }

    public function test_get_users_salt_from_secret_key(): void
    {
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';

        $salt = EncryptionHelper::getUsersSalt($secretKey);

        $this->assertIsString($salt);
        $this->assertNotEmpty($salt);
    }

    public function test_get_users_encrypt_key(): void
    {
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';
        $password = 'mypassword123';

        $encryptKey = EncryptionHelper::getUsersEncryptKey($secretKey, $password);

        $this->assertIsString($encryptKey);
        $this->assertNotEmpty($encryptKey);
    }

    public function test_get_family_encryption_key(): void
    {
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';

        $encryptKey = EncryptionHelper::getFamilyEncryptionKey($secretKey);

        $this->assertIsString($encryptKey);
        $this->assertNotEmpty($encryptKey);
    }

    public function test_generate_asymmetric_key_returns_key_pair(): void
    {
        $keyPair = EncryptionHelper::generateAsymmetricKey();

        $this->assertIsArray($keyPair);
        $this->assertArrayHasKey('private', $keyPair);
        $this->assertArrayHasKey('public', $keyPair);
        $this->assertNotEmpty($keyPair['private']);
        $this->assertNotEmpty($keyPair['public']);
    }

    public function test_decrypt_throws_exception_with_invalid_data(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Decryption failed');

        // Use properly formatted but invalid data (correct IV length)
        $validIvLength = base64_encode(random_bytes(16));
        $invalidData = base64_encode('corrupted_encrypted_data');
        EncryptionHelper::decrypt($invalidData, $validIvLength);
    }

    public function test_decrypt_from_string_throws_exception_with_invalid_data(): void
    {
        $this->expectException(SecurityException::class);

        // Use properly formatted but invalid data (correct IV length)
        $validIvLength = base64_encode(random_bytes(16));
        $invalidData = base64_encode('corrupted_encrypted_data');
        EncryptionHelper::decryptFromString($validIvLength . '.' . $invalidData);
    }

    public function test_get_system_secret_key_returns_combined_keys(): void
    {
        // Temporarily set environment variables using config
        config(['app.main_secret_key' => 'test_secret_key_12345']);
        config(['app.main_salt_key' => 'test_salt_key_67890']);
        
        // Mock env() to return config values
        putenv('MAIN_SECRET_KEY=test_secret_key_12345');
        putenv('MAIN_SALT_KEY=test_salt_key_67890');
        
        $result = EncryptionHelper::getSystemSecretKey();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_system_secret_key_throws_exception_when_key_missing(): void
    {
        // Clear environment variables
        putenv('MAIN_SECRET_KEY');
        putenv('MAIN_SALT_KEY');

        $this->expectException(EncryptionException::class);
        $this->expectExceptionMessage('MAIN_SECRET_KEY is not set');

        EncryptionHelper::getSystemSecretKey();
    }
}
