<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EncryptionException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\User\UserService;
use App\Services\UserConfig\UserConfigService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private UserService $userService;

    private UserConfigService $userConfig;

    public function __construct(UserService $userService, UserConfigService $userConfig)
    {
        $this->userService = $userService;
        $this->userConfig = $userConfig;
    }

    /**
     * Rotating the password and/or the UANGKU-XXXX secret key are the same
     * operation from the server's perspective (both just swap salt/verifier/
     * wrapped-private-key) — see AuthController::preChangePassword /
     * AuthController::changePassword.
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $this->userService->getProfile($request->bearerToken());
            $userConfig = $this->userConfig->getConfigByUserId($user['id']);

            if ($user['family'] == null) {
                return response()->json(new BaseResponse(
                    status: 200,
                    message: 'Success to get user profile',
                    resource: [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'avatar' => $user['avatar'],
                        'config' => $userConfig,
                        'family' => null,
                    ]
                ));
            }

            return response()->json(new BaseResponse(
                status: 200,
                message: 'Success to get user profile',
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
            Log::error('Failed to get user profile : '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to get user profile', $e->getMessage()), 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to update user profile', $validator->errors()), 400);
        }

        try {
            $this->userService->updateProfile(
                token: $request->bearerToken(),
                name: $request->name
            );

            return response()->json(new BaseResponse(200, 'Success to update user profile', $request->all()), 200);
        } catch (Exception $e) {
            Log::error('Failed to update user profile : '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to update user profile : '.$e->getMessage()), 500);
        }
    }

    public function updateDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to update user date', $validator->errors()), 400);
        }

        try {
            $this->userConfig->setDate(
                token: $request->bearerToken(),
                date: $request->date
            );

            return response()->json(new BaseResponse(
                status: 200,
                message: 'Success to update user date',
                resource: null
            ), 200);
        } catch (UserException|EncryptionException $e) {
            Log::error('Failed to update user date : '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to update user date : '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to update user date : '.$e->getMessage()), 500);
        }
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to update user avatar', $validator->errors()), 400);
        }

        try {

            $url = $this->userService->updateAvatar(
                token: $request->bearerToken(),
                avatar: $request->file('avatar')
            );

            return response()->json(new BaseResponse(
                status: 200,
                message: 'Success to update user avatar',
                resource: [
                    'avatar' => $url,
                ]
            ), 200);
        } catch (Exception $e) {
            Log::error('Failed to update user avatar : '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to update user avatar : '.$e->getMessage()), 500);
        }
    }
}
