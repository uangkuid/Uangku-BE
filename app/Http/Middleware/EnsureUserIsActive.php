<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Http\Resources\BaseResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak setiap request dari user yang di-suspend/ban.
 * Berjalan di seluruh grup 'api'; hanya aktif bila ada user terautentikasi.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = auth('api')->user();
        } catch (\Throwable) {
            // Token invalid/expired bukan urusan middleware ini — biarkan auth:api yang menangani.
            return $next($request);
        }

        if ($user && $user->status instanceof UserStatus && $user->status->isBlocked()) {
            return response()->json(
                new BaseResponse(403, 'Akun Anda telah dinonaktifkan. Silakan hubungi dukungan.', null),
                403
            );
        }

        return $next($request);
    }
}
