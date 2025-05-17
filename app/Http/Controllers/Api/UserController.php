<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EncryptionException;
use App\Exceptions\SecurityException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\UserConfig;
use App\Services\User\UserService;
use App\Services\UserConfig\UserConfigService;
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
    private UserConfigService $userConfig;

    public function __construct(UserService $userService, UserConfigService $userConfig)
    {
        $this->userService = $userService;
        $this->userConfig = $userConfig;
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
        try {
            $user = $this->userService->getProfile($request->bearerToken());
            $userConfig = $this->userConfig->getConfigByUserId($user['id']);

            return response()->json(new BaseResponse(
                status: 200,
                message: "Success to get user profile",
                resource: [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'avatar' => $user['avatar'],
                    'config' => $userConfig,
                    'family' => [
                        'id' => $user['family']['family'],
                        'role' => $user['family']['role'],
                    ],
                ]
            ), 200);
        } catch (Exception $e) {
            Log::error("Failed to get user profile : " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to get user profile", $e->getMessage()), 500);
        }
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

    function updateDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update user date", $validator->errors()), 400);
        }

        try {
            $this->userConfig->setDate(
                token: $request->bearerToken(),
                date: $request->date
            );

            return response()->json(new BaseResponse(
                status: 200,
                message: "Success to update user date",
                resource: null
            ), 200);
        } catch (UserException|EncryptionException $e) {
            Log::error("Failed to update user date : " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to update user date : " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to update user date : " . $e->getMessage()), 500);
        }
    }

    function updateAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update user avatar", $validator->errors()), 400);
        }

        try {

            $url = $this->userService->updateAvatar(
                token: $request->bearerToken(),
                avatar: $request->file('avatar')
            );

            return response()->json(new BaseResponse(
                status: 200,
                message: "Success to update user avatar",
                resource: [
                    "avatar" => $url
                ]
            ), 200);
        } catch (Exception $e) {
            Log::error("Failed to update user avatar : " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to update user avatar : " . $e->getMessage()), 500);
        }
    }
}
