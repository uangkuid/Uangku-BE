<?php

namespace App\Http\Middleware;

use App\Enums\TokenType;
use App\Http\Resources\BaseResponse;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The JWT guard accepts any token signed with our secret, so on its own it
 * cannot tell a refresh token from an access token. This middleware must sit
 * next to every `auth:api` and only lets `typ: access` through. Tokens
 * without a `typ` claim are rejected as well.
 */
class EnsureAccessToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Inspect the very token the guard authenticated with.
            $type = auth('api')->payload()->get('typ');
        } catch (JWTException) {
            $type = null;
        }

        if ($type !== TokenType::Access->value) {
            return response()->json(new BaseResponse(401, 'Unauthenticated.', null), 401);
        }

        return $next($request);
    }
}
