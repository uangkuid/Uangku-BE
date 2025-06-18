<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResponse;
use App\Services\User\UserService;
use App\Services\Wallet\WalletService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletAdminMiddleware
{

    protected UserService $userService;
    protected WalletService $walletService;

    public function __construct(UserService $userService, WalletService $walletService)
    {
        $this->userService = $userService;
        $this->walletService = $walletService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->userService->getUserByToken($request->bearerToken());
        $id = $request->route('id');

        if ($id != null && $id != '') {
            $isExist = $this->walletService->isHasAdminAccess(walletId: $id, userId: $user->id);

            if (!$isExist) {
                return response()->json(new BaseResponse(403, "You do not have admin access to this wallet!"), 403);
            }

            return $next($request);
        } else {
            return response()->json(new BaseResponse(400, "Wallet ID is required!"), 400);
        }
    }
}
