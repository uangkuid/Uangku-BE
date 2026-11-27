<?php

namespace App\Helpers;

use App\Enums\TokenType;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Token;
use SensitiveParameter;

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

    /**
     * Verify signature, expiry and token type.
     *
     * The blacklist is deliberately NOT consulted: refresh-token reuse
     * detection has to recognise a token that was already rotated (and thus
     * blacklisted). Callers must check isBlacklisted() themselves.
     *
     * @throws JWTException TokenExpiredException when expired, otherwise invalid
     */
    public static function decode(#[SensitiveParameter] string $token, TokenType $type): Payload
    {
        $payload = JWTAuth::manager()->decode(new Token($token), false);

        if ($payload->get('typ') !== $type->value) {
            throw new TokenInvalidException('Unexpected token type');
        }

        return $payload;
    }

    public static function isBlacklisted(Payload $payload): bool
    {
        return JWTAuth::manager()->getBlacklist()->has($payload);
    }

    /**
     * Put a still-valid token on the blacklist.
     *
     * @throws JWTException
     */
    public static function invalidate(#[SensitiveParameter] string $token): void
    {
        try {
            JWTAuth::setToken($token)->invalidate();
        } finally {
            JWTAuth::unsetToken();
        }
    }
}
