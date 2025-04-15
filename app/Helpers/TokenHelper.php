<?php

namespace App\Helpers;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TokenHelper
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public static function generateRefreshToken(User $user): string
    {
        // Set TTL untuk refresh token menjadi lebih panjang (misalnya 7 hari)
        // Set masa berlaku refresh token secara manual (contoh: 7 hari)
        $customClaims = ['exp' => now()->addDays(7)->timestamp];

        // Buat refresh token dengan masa berlaku yang lebih panjang
        $refreshToken = JWTAuth::claims($customClaims)->fromUser($user);

        return $refreshToken;
    }
}
