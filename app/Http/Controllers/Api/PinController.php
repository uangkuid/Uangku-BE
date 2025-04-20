<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Exceptions\PinException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\Pin\PinService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PinController extends Controller
{

    protected PinService $pinService;

    public function __construct(PinService $pinService)
    {
        $this->pinService = $pinService;
    }

    public function store(Request $request)
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


        } catch (AuthException|PinException $e) {
            Log::error($e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(new BaseResponse(500, "Failed create PIN", $e->getMessage()), 500);
        }
    }

    public function init(Request $request)
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
}
