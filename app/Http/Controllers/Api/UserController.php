<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SecurityException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\User\UserService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{

    private UserService $userService;
    private WalletService $walletService;

    public function __construct(UserService $userService, WalletService $walletService)
    {
        $this->userService = $userService;
        $this->walletService = $walletService;
    }

    function preGenerateSecretKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', Password::default()]
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to regenerate secret key", $validator->errors()), 400);
        }

        try {
            $this->userService->preRegenerateSecretKey(
                token: $request->bearerToken(),
                password: $request->password
            );

            return response()->json(new BaseResponse(
                status: 200,
                message: "Success to regenerate secret key",
                resource: null
            ), 200);
        } catch (UserException|SecurityException $e) {
            Log::error("Failed to regenerate secret key : " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to regenerate secret key : " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to regenerate secret key : " . $e->getMessage()), 500);
        }
    }

    function generateSecretKey(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'secret_key' => ['required'],
            'otp' => ['required'],
            'uuid' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to generate secret key", $validator->errors()), 400);
        }

        try {
            $secretKey = $this->userService->generateSecretKey(
                token: $request->bearerToken(),
                oldSecretKey: $request->secret_key,
                otp: $request->otp,
                uuid: $request->uuid
            );

            return response()->json(new BaseResponse(
                status: 200,
                message: "Success to generate secret key",
                resource: [
                    'secret_key' => $secretKey
                ]
            ), 200);
        } catch (UserException|SecurityException $e) {
            Log::error("Failed to generate secret key : " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to generate secret key : " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to generate secret key : " . $e->getMessage()), 500);
        }
    }

    function getProfile(Request $request): JsonResponse
    {
        $user = $this->userService->getUserByToken($request->bearerToken());

        return response()->json(new BaseResponse(
            status: 200,
            message: "Success to get user profile",
            resource: [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ]
        ), 200);
    }

    function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update user profile", $validator->errors()), 400);
        }

        try {
            $this->userService->updateProfile(
                token: $request->bearerToken(),
                name: $request->name
            );

            return response()->json(new BaseResponse(200, "Success to update user profile", $request->all()), 200);
        } catch (Exception $e) {
            Log::error("Failed to update user profile : " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to update user profile : " . $e->getMessage()), 500);
        }
    }
}
