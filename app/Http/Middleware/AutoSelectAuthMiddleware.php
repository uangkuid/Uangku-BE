<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoSelectAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasFamilyKey = $request->hasHeader('family_secret_key');
        $hasPersonalKey = $request->hasHeader('personal_secret_key');
        $hasFamilyId = $request->has('family_id');

        // Jika kedua header ada, gunakan FamilyMiddleware
        if ($hasFamilyId && $hasPersonalKey) {
            return app(FamilyMiddleware::class)->handle($request, $next);
        }
        // Jika hanya personal_secret_key yang ada, gunakan PersonalMiddleware
        elseif ($hasPersonalKey) {
            return app(PersonalMiddleware::class)->handle($request, $next);
        }
        // Jika kondisi tidak sesuai, tolak permintaan
        else {
            return response()->json(new BaseResponse(403, "You must send secret key from header!"), 403);
        }
//        return $next($request);
    }
}
