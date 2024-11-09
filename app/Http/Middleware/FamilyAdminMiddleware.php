<?php

namespace App\Http\Middleware;

use App\Enums\RoleFamily;
use App\Http\Resources\BaseResponse;
use App\Models\FamilyMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FamilyAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $familyMember = FamilyMember::where('family', $request->route('id'))
            ->where('user', $request->user()->id)
            ->where('role', RoleFamily::Admin);

        if ($familyMember->count() > 0) {
            return $next($request);
        } else {
            return response()->json(new BaseResponse(403, "You not have permission to modify on family!"), 403);
        }
    }
}
