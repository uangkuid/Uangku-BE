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
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Periksa keberadaan header dan parameter
        $hasFamilyKey = $request->hasHeader('family_secret_key');
        $hasPersonalKey = $request->hasHeader('personal_secret_key');
        $hasFamilyId = $request->has('family_id');

        // 1. Jika ada `family_secret_key` tanpa `family_id`, tolak permintaan
        if ($hasFamilyKey && !$hasFamilyId) {
            return response()->json(new BaseResponse(403, "family_id is required with family_secret_key."), 403);
        }

        // 2. Jika ada `family_id` dan `family_secret_key`, gunakan FamilyMiddleware
        if ($hasFamilyKey && $hasFamilyId) {
            return app(FamilyMiddleware::class)->handle($request, $next);
        }

        // 3. Jika hanya ada `personal_secret_key`, gunakan PersonalMiddleware
        if ($hasPersonalKey && !$hasFamilyKey && !$hasFamilyId) {
            return app(PersonalMiddleware::class)->handle($request, $next);
        }

        // 4. Jika tidak memenuhi syarat di atas, tolak permintaan
        return response()->json(new BaseResponse(403, "Invalid Request!"), 403);
    }
}
