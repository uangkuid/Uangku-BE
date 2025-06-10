<?php

namespace App\Http\Middleware;

use App\Helpers\EncryptionHelper;
use App\Http\Resources\BaseResponse;
use App\Models\FamilyMember;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
//        // Periksa keberadaan header dan parameter
//        $hasFamilyKey = $request->hasHeader('family_secret_key');
//        $hasPersonalKey = $request->hasHeader('personal_secret_key');
//        $hasFamilyId = $request->has('family_id');
//
//        // 1. Jika ada `family_secret_key` tanpa `family_id`, tolak permintaan
//        if ($hasFamilyKey && !$hasFamilyId) {
//            return response()->json(new BaseResponse(403, "family_id is required with family_secret_key."), 403);
//        }
//
//        /**
//         * Decode Secret Key Family
//         */
//        if ($hasFamilyKey && $hasFamilyId) {
//            $id = $request->family_id;
//
//            $familyMember = FamilyMember::select('id')
//                ->where('family', $id)
//                ->where('user', $request->user()->id)
//                ->limit(1);
//
//            if ($familyMember->count() < 1) {
//                return response()->json(new BaseResponse(403, "You not authorized to do this action!"), 403);
//            }
//
//            $secretKey = $request->header('family_secret_key');
//
//            $request->merge(['family_secret_key' => $secretKey]);
//        }
//
//        /**
//         * Decode Secret Key Personal
//         */
//        if ($hasPersonalKey) {
//            $secretKey = $request->header('personal_secret_key');
//
//            try {
//                $secretKey = EncryptionHelper::decryptFromString($secretKey, EncryptionHelper::getSystemSecretKey());
//                $request->merge(['personal_secret_key' => $secretKey]);
//            } catch (Exception $e) {
//                return response()->json(new BaseResponse(403, "Something wrong: ", $e->getMessage()), 403);
//            }
//        }

        return $next($request);
    }
}
