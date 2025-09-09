<?php

namespace App\Helpers;

use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Random\RandomException;

class EncryptionHelper
{
    /**
     * Encrypt the given data using AES-CBC.
     *
     * @param string $data
     * @param string|null $key
     * @return array
     * @throws RandomException
     * @throws EncryptionException
     */
    public static function encrypt(string $data, string $key = null): array
    {
        // Use dynamic secret key from .env or default value
        $key = $key ?? env('MAIN_SECRET_KEY', 'Password');

        // Ensure the key is the right length for AES-256
        $key = substr(hash('sha256', $key, true), 0, 32);

        // Generate a random Initialization Vector (IV)
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Encrypt the data using AES-256-CBC
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

        if ($encryptedData === false) {
            throw new EncryptionException('Encryption failed.');
        }

        // Return encrypted data and the IV used
        return [
            'iv' => base64_encode($iv),
            'data' => base64_encode($encryptedData),
        ];
    }

    /**
     * Encrypt the given data using asymmetric encryption with a public key.
     * @param string $data
     * @param string $publicKey
     * @return string
     * @throws EncryptionException
     */
    public static function encryptAsymmetric(string $data, string $publicKey): string
    {
        // Encrypt the data using the public key
        $isSuccess = openssl_public_encrypt($data, $encryptedData, $publicKey);

        if ($isSuccess === false) {
            throw new EncryptionException('Encryption failed.');
        }

        // Return the encrypted data
        return base64_encode($encryptedData);
    }

    /**
     * Encrypt the given data using AES-CBC.
     *
     * @param string $data
     * @param string|null $key
     * @param string|null $iv
     * @return string with pattern iv + '.' + encryptedData
     * @throws RandomException
     * @throws EncryptionException
     */
    public static function encryptAsString(string $data, string $key = null, string $iv = null): string
    {
        // Use dynamic secret key from .env or default value
        $key = $key ?? env('MAIN_SECRET_KEY', 'Password');

        // Ensure the key is the right length for AES-256
        $key = substr(hash('sha256', $key, true), 0, 32);

        // Generate a random Initialization Vector (IV)

        $iv = $iv ?? random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Encrypt the data using AES-256-CBC
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

        if ($encryptedData === false) {
            throw new EncryptionException('Encryption failed.');
        }

        // Return encrypted data and the IV used
        return base64_encode($iv) . '.' . base64_encode($encryptedData);
    }

    /**
     * Hash the given secret key using a salt.
     * @param string $secretKey
     * @return string
     */
    public static function hashSecretKey(string $secretKey): string
    {
        $salt = env('MAIN_SALT_KEY', 'Password');
        return Hash::make($salt . $secretKey . $salt);
    }

    /**
     * Validate the given secret key against the hashed data.
     * @param string $inputKey
     * @param string $hashedData
     * @return bool
     */
    public static function validateSecretKey(string $inputKey, string $hashedData): bool
    {
        $salt = env('MAIN_SALT_KEY', 'Password');
        return Hash::check($salt . $inputKey . $salt, $hashedData);
    }

    /**
     * Decrypt the given data using AES-CBC.
     *
     * @param string $encryptedData
     * @param string $iv
     * @param string|null $key
     * @return string
     * @throws SecurityException
     */
    public static function decrypt(string $encryptedData, string $iv, string $key = null): string
    {
        // Use dynamic secret key from .env or default value
        $key = $key ?? env('MAIN_SECRET_KEY', 'Password');

        // Ensure the key is the right length for AES-256
        $key = substr(hash('sha256', $key, true), 0, 32);

        // Decode the IV and encrypted data from base64
        $iv = base64_decode($iv);
        $encryptedData = base64_decode($encryptedData);

        // Decrypt the data using AES-256-CBC
        $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $key, 0, $iv);

        if ($decryptedData === false) {
            throw new SecurityException('Decryption failed. Invalid key or data.');
        }

        return $decryptedData;
    }

    /**
     * Decrypt the given data using asymmetric encryption with a private key.
     * @param string $encryptedData
     * @param string $privateKey
     * @return string
     * @throws SecurityException
     */
    public static function decryptAsymmetric(string $encryptedData, string $privateKey): string
    {
        // Decrypt the data using the private key
        $isSuccess = openssl_private_decrypt(base64_decode($encryptedData), $decryptedData, $privateKey);

        if ($isSuccess === false) {
            throw new SecurityException('Decryption failed. Invalid key or data.');
        }

        // Return the decrypted data
        return $decryptedData;
    }

    /**
     * Decrypt the given data using AES-CBC
     *
     * @param string $encryptedData
     * @param string|null $key
     * @return string
     * @throws SecurityException
     */
    public static function decryptFromString(string $encryptedData, string $key = null): string
    {
        // Use dynamic secret key from .env or default value
        $key = $key ?? env('MAIN_SECRET_KEY', 'Password');

        // Ensure the key is the right length for AES-256
        $key = substr(hash('sha256', $key, true), 0, 32);

        $dataAsArray = explode('.', $encryptedData);

        $iv = base64_decode($dataAsArray[0]);
        $encryptedData = base64_decode($dataAsArray[1]);

        // Decrypt the data using AES-256-CBC
        $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $key, 0, $iv);

        if ($decryptedData === false) {
            throw new SecurityException('Decryption failed. Invalid key or data.');
        }

        return $decryptedData;
    }

    /**
     * Generate a secret key in the format "XXXX-XXXXXX-XXXXXX-XXXXX-XXXXX-XXXXX".
     *
     * @return string
     * @throws RandomException
     */
    public static function generateUsersSecretKey(): string
    {
        // Generate 20 random bytes and encode in Base32 for readability
        $randomBytes = random_bytes(128);
        $base32Key = strtoupper(str_replace(['=', '+', '/'], '', base64_encode($randomBytes)));

        // Split the Base32 encoded string into blocks with specified length
        return "UANGKU" . '-' .
            substr($base32Key, 0, 6) . '-' .
            substr($base32Key, 6, 6) . '-' .
            substr($base32Key, 12, 5) . '-' .
            substr($base32Key, 17, 5) . '-' .
            substr($base32Key, 22, 5);
    }

    /**
     * Get system secret key and salt.
     * @return string
     * @throws EncryptionException
     */
    public static function getSystemSecretKey(): string
    {
        $secretKey = env('MAIN_SECRET_KEY') ?? throw new EncryptionException('MAIN_SECRET_KEY is not set in .env file.');
        $saltKey = env('MAIN_SALT_KEY') ?? throw new EncryptionException('MAIN_SECRET_KEY is not set in .env file.');
        return $secretKey . $saltKey;
    }

    /**
     * Get Users salt using given secret key
     * @param $secretKey
     * @return string
     */
    public static function getUsersSalt($secretKey): string
    {
        $secretKeyAsArray = explode("-", $secretKey);
        return self::xorString($secretKeyAsArray[1] . "-" . $secretKeyAsArray[0] . "-" . end($secretKeyAsArray), 16);
    }

    /**
     * XOR a string with an integer key.
     *
     * @param string $string The input string to be XORed.
     * @param int $key The integer key for XOR operation.
     * @return string The XORed result as a string.
     */
    public static function xorString(string $string, int $key): string
    {
        $result = '';

        // Iterate over each character in the string
        for ($i = 0; $i < strlen($string); $i++) {
            // XOR each character with the key and append to result
            $result .= chr(ord($string[$i]) ^ $key);
        }

        return $result;
    }

    /**
     * Generate an encrypted key for a user based on the provided secret key and password.
     *
     * This method concatenates a salt, the user's password, and a sanitized version of
     * the secret key to create a unique encrypted key for the user.
     *
     * @param string $secretKey The user's unique secret key.
     * @param string $password The user's password to be included in the key generation.
     *
     * @return string A unique encrypted key for the user.
     */
    public static function getUsersEncryptKey(string $secretKey, string $password): string
    {
        $salt = self::getUsersSalt($secretKey);
        $secretKeySanitize = str_replace("-", "", $secretKey);
        return self::xorString(($salt . $password . $secretKeySanitize), 8);
    }

    /**
     * Generate an encrypted key for a family based on the provided secret key.
     *
     * This method concatenates a salt, and a sanitized version of
     * the secret key to create a unique encrypted key for the family.
     *
     * @param string $secretKey The user's unique secret key.
     *
     * @return string A unique encrypted key for the user.
     */
    public static function getFamilyEncryptionKey(string $secretKey): string
    {
        $salt = self::getUsersSalt($secretKey);
        $secretKeySanitize = str_replace("-", "", $secretKey);
        $secretKeyAsArray = explode("-", $secretKey);
        return $salt . $secretKeyAsArray[1] . $secretKeySanitize;
    }

    /**
     * Generate a random asymmetric key pair.
     * @return array a pair of keys public and private
     */
    public static function generateAsymmetricKey(): array
    {
        // Konfigurasi untuk pembuatan kunci
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Membuat pasangan kunci
        $res = openssl_pkey_new($config);

        // Ekstrak kunci privat
        openssl_pkey_export($res, $privateKey);

        // Ekstrak kunci publik
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails["key"];
        return [
            "private" => base64_encode($privateKey),
            "public" => base64_encode($publicKey),
        ];
    }
}
