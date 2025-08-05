<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResponse;
use App\Services\User\UserService;
use App\Services\Wallet\WalletService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletMiddleware
{

    protected WalletService $walletService;
    protected UserService $userService;

    /**
     * @param WalletService $walletService
     * @param UserService $userService
     */
    public function __construct(WalletService $walletService, UserService $userService)
    {
        $this->walletService = $walletService;
        $this->userService = $userService;
    }


    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->userService->getUserByToken($request->bearerToken());

        $id = $request->wallet;

        if ($id != null && $id != '') {
            $isExist = $this->walletService->isHasAccess(walletId: $id, userId: $user->id);

            if (!$isExist) {
                return response()->json(new BaseResponse(403, "You not authorized to do this action!"), 403);
            }

            // Jika ada, lanjutkan ke request berikutnya
            return $next($request);
        }

        $id = $request->route('id');

        if ($id != null && $id != '') {
            $isExist = $this->walletService->isHasAccess(walletId: $id, userId: $user->id);

            if (!$isExist) {
                return response()->json(new BaseResponse(403, "You not authorized to do this action!"), 403);
            }

            // Jika ada, lanjutkan ke request berikutnya
            return $next($request);
        }

        return response()->json(new BaseResponse(400, "Wallet ID is required!"), 400);
    }
}
