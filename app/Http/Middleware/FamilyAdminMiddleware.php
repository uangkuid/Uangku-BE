<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResponse;
use App\Services\Family\FamilyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FamilyAdminMiddleware
{

    protected FamilyService $familyService;

    public function __construct(FamilyService $familyService)
    {
        $this->familyService = $familyService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $isExist = $this->familyService->isHasAdminAccess($request->route('id'), $request->bearerToken());

        if ($isExist) {
            return $next($request);
        } else {
            return response()->json(new BaseResponse(403, "You not have permission admin access on family!"), 403);
        }
    }
}
