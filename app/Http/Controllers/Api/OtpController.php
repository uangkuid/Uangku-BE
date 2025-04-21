<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\Otp\OtpService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{

    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    function sendRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to send otp", $validator->errors()), 400);
        }

        try {
            $otp = $this->otpService->sendRegister($request->email);

            return response()->json(new BaseResponse(200, "OTP sent successfully", $otp), 200);
        } catch (AuthException $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(500, "Failed to send otp", $e->getMessage()), 500);
        }
    }

    function sendChangePassword(Request $request): JsonResponse
    {
        try {
            $otp = $this->otpService->sendChangePassword($request->bearerToken());

            return response()->json(new BaseResponse(200, "OTP sent successfully", $otp), 200);
        } catch (AuthException $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(500, "Failed to send otp", $e->getMessage()), 500);
        }
    }

    function sendForgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to send otp", $validator->errors()), 400);
        }

        try {
            $otp = $this->otpService->sendForgotPassword($request->email);

            return response()->json(new BaseResponse(200, "OTP sent successfully", $otp), 200);
        } catch (AuthException $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(500, "Failed to send otp", $e->getMessage()), 500);
        }
    }

    function sendPin(Request $request): JsonResponse
    {
        try {
            $otp = $this->otpService->sendPin($request->bearerToken());

            return response()->json(new BaseResponse(200, "OTP sent successfully", $otp), 200);
        } catch (AuthException $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(500, "Failed to send otp", $e->getMessage()), 500);
        }
    }

    function sendForgotPin(Request $request): JsonResponse
    {
        try {
            $otp = $this->otpService->sendForgotPin($request->bearerToken());

            return response()->json(new BaseResponse(200, "OTP sent successfully", $otp), 200);
        } catch (AuthException $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to send otp", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(new BaseResponse(500, "Failed to send otp", $e->getMessage()), 500);
        }
    }
}
