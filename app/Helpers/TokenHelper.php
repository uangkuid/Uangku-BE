<?php

namespace App\Helpers;

use App\Enums\TokenType;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TokenHelper
{
    /**
     * Issue a long-lived refresh token (`typ: refresh`).
     *
     * Inline claims are merged after JWTSubject::getJWTCustomClaims() (see
     * JWT::getClaimsArray), so `typ` here wins over the access default.
     */
    public static function generateRefreshToken(User $user): string
    {
        $ttlDays = (int) config('jwt.refresh_token_ttl_days');

        try {
            return JWTAuth::claims([
                'typ' => TokenType::Refresh->value,
                'exp' => now()->addDays($ttlDays)->timestamp,
            ])->fromUser($user);
        } finally {
            // JWTAuth keeps inline claims on the instance. Without this reset
            // the next token issued through it would also be a 7-day refresh token.
            JWTAuth::claims([]);
        }
    }
}
