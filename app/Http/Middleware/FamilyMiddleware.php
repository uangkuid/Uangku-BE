<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResponse;
use App\Services\Family\FamilyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FamilyMiddleware
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

        $id = $request->route('id');

        if ($id != null && $id != '') {

            $isExist = $this->familyService->isHasAccess($id, $request->bearerToken());

            if (!$isExist) {
                return response()->json(new BaseResponse(403, "You not authorized to do this action!"), 403);
            }

            return $next($request);
        } else {
            return response()->json(new BaseResponse(400, "Family ID is required!"), 400);
        }
    }
}
