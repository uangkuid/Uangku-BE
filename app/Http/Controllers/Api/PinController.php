<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Exceptions\PinException;
use App\Exceptions\SecurityException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\Pin\PinService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js;

class PinController extends Controller
{

    protected PinService $pinService;

    public function __construct(PinService $pinService)
    {
        $this->pinService = $pinService;
    }

    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'pin' => 'required|confirmed|numeric|digits:6',
            'uuid' => 'required',
            'otp' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed create PIN", $validator->errors()), 400);
        }

        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if ($isPinEnable) {
                return response()->json(new BaseResponse(400, "PIN is already enabled"), 400);
            }

            $this->pinService->createPin(
                token: $request->bearerToken(),
                pin: $request->pin,
                uuid: $request->uuid,
                otp: $request->otp
            );

            return response()->json(new BaseResponse(200, "Create PIN success"), 200);
        } catch (AuthException|PinException $e) {
            Log::error($e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(new BaseResponse(500, "Failed create PIN", $e->getMessage()), 500);
        }
    }

    public function init(Request $request): JsonResponse
    {
        try {
            $this->pinService->initPin($request->bearerToken());

            return response()->json(new BaseResponse(200, "Init pin success"), 200);
        } catch (PinException $e) {
            Log::error("Failed init PIN " .$e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed init PIN " .$e->getMessage());
            return response()->json(new BaseResponse(500, "Failed init PIN", $e->getMessage()), 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if (!$isPinEnable) {
                return response()->json(new BaseResponse(400, "PIN is already disabled"), 400);
            }

            $this->pinService->deletePin($request->bearerToken());

            return response()->json(new BaseResponse(200, "Delete PIN success"), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error("Failed delete PIN " .$e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed delete PIN " .$e->getMessage());
            return response()->json(new BaseResponse(500, "Failed delete PIN", $e->getMessage()), 500);
        }
    }

    public function forgot(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed forgot PIN", $validator->errors()), 400);
        }

        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if (!$isPinEnable) {
                return response()->json(new BaseResponse(400, "PIN is currently disabled"), 400);
            }

            $this->pinService->forgotPin(
                token: $request->bearerToken(),
                password: $request->password
            );

            return response()->json(new BaseResponse(200, "Forgot PIN success"), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error("Failed forgot PIN " .$e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed forgot PIN " .$e->getMessage());
            return response()->json(new BaseResponse(500, "Failed forgot PIN", $e->getMessage()), 500);
        }
    }

    public function reset(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'pin' => 'required|confirmed|numeric|digits:6',
            'uuid' => 'required',
            'otp' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed reset PIN", $validator->errors()), 400);
        }

        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if (!$isPinEnable) {
                return response()->json(new BaseResponse(400, "PIN is currently disabled"), 400);
            }

            $this->pinService->resetPin(
                token: $request->bearerToken(),
                pin: $request->pin,
                uuid: $request->uuid,
                otp: $request->otp
            );

            return response()->json(new BaseResponse(200, "Reset PIN success"), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error("Failed reset PIN " .$e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed reset PIN " .$e->getMessage());
            return response()->json(new BaseResponse(500, "Failed reset PIN", $e->getMessage()), 500);
        }
    }
}
