<?php

namespace App\Http\Middleware;

use App\Helpers\EncryptionHelper;
use App\Http\Resources\BaseResponse;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonalMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretKey = $request->header('personal_secret_key');

        if (!empty($secretKey)) {
//            return $next($request);
            try {
                $request->merge(['personal_secret_key' => $secretKey]);

                return $next($request);
            } catch (Exception $e) {
                return response()->json(new BaseResponse(403, "Something wrong: ", $e->getMessage()), 403);
            }
        } else {
            return response()->json(new BaseResponse(403, "You not authorized to do this action!, missing personal secret key!"), 403);
        }
    }
}
