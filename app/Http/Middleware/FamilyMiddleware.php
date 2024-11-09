<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResponse;
use App\Models\FamilyMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FamilyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretKey = $request->header('family_secret_key');

        $id = $request->route('id');

        if ($id != null && $id != '') {
            $familyMember = FamilyMember::where('family', $id)
                ->where('user', $request->user()->id);

            if ($familyMember->count() < 1) {
                return response()->json(new BaseResponse(403, "You not authorized to do this action!"), 403);
            }
        }

        if (!empty($secretKey)) {
            return $next($request);
        } else {
            return response()->json(new BaseResponse(403, "You not authorized to do this action!"), 403);
        }
    }
}
