<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Str;

class EncryptionHelper
{
    /**
     * Encrypt the given data using AES-CBC.
     *
     * @param string $data
     * @param string $key
     * @return array
     * @throws Exception
     */
    public static function encrypt(string $data, string $key = null): array
    {
        // Use dynamic secret key from .env or default value
        $key = $key ?? env('ENCRYPTION_KEY', 'Password');

        // Ensure the key is the right length for AES-256
        $key = substr(hash('sha256', $key, true), 0, 32);

        // Generate a random Initialization Vector (IV)
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Encrypt the data using AES-256-CBC
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

        if ($encryptedData === false) {
            throw new Exception('Encryption failed.');
        }

        // Return encrypted data and the IV used
        return [
            'encrypted_data' => base64_encode($encryptedData),
            'iv' => base64_encode($iv),
        ];
    }

    /**
     * Decrypt the given data using AES-CBC.
     *
     * @param string $encryptedData
     * @param string $iv
     * @param string $key
     * @return string
     * @throws Exception
     */
    public static function decrypt(string $encryptedData, string $iv, string $key = null): string
    {
        // Use dynamic secret key from .env or default value
        $key = $key ?? env('ENCRYPTION_KEY', 'Password');

        // Ensure the key is the right length for AES-256
        $key = substr(hash('sha256', $key, true), 0, 32);

        // Decode the IV and encrypted data from base64
        $iv = base64_decode($iv);
        $encryptedData = base64_decode($encryptedData);

        // Decrypt the data using AES-256-CBC
        $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $key, 0, $iv);

        if ($decryptedData === false) {
            throw new Exception('Decryption failed.');
        }

        return $decryptedData;
    }
}
