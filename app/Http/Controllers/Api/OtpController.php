<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Exceptions\SecurityException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\Otp\OtpService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class OtpController extends Controller
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    #[OA\Post(
        path: '/otp/send/register',
        summary: 'Send registration OTP',
        description: 'Sends a one-time-password to the given email to complete registration via /auth/register.',
        tags: ['OTP'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP sent successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or sending failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function sendRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to send otp', $validator->errors()), 400);
        }

        try {
            $otp = $this->otpService->sendRegister($request->email);

            return response()->json(new BaseResponse(200, 'OTP sent successfully', $otp), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(500, 'Failed to send otp', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/otp/send/change-password',
        summary: 'Send change-password OTP',
        description: 'Sends a one-time-password to the authenticated user\'s email, required to confirm /auth/change-password.',
        security: [['bearerAuth' => []]],
        tags: ['OTP'],
        responses: [
            new OA\Response(response: 200, description: 'OTP sent successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Sending failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function sendChangePassword(Request $request): JsonResponse
    {
        try {
            $otp = $this->otpService->sendChangePassword($request->bearerToken());

            return response()->json(new BaseResponse(200, 'OTP sent successfully', $otp), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(500, 'Failed to send otp', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/otp/send/forgot-password',
        summary: 'Send forgot-password OTP',
        description: 'Sends a one-time-password to the given email, required to confirm /auth/reset-password.',
        tags: ['OTP'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP sent successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or sending failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function sendForgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to send otp', $validator->errors()), 400);
        }

        try {
            $otp = $this->otpService->sendForgotPassword($request->email);

            return response()->json(new BaseResponse(200, 'OTP sent successfully', $otp), 200);
        } catch (SecurityException $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(500, 'Failed to send otp', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/otp/send/pin',
        summary: 'Send create-PIN OTP',
        description: 'Sends a one-time-password to the authenticated user\'s email, required to confirm /auth/pin (PIN creation).',
        security: [['bearerAuth' => []]],
        tags: ['OTP'],
        responses: [
            new OA\Response(response: 200, description: 'OTP sent successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Sending failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function sendPin(Request $request): JsonResponse
    {
        try {
            $otp = $this->otpService->sendPin($request->bearerToken());

            return response()->json(new BaseResponse(200, 'OTP sent successfully', $otp), 200);
        } catch (SecurityException $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(500, 'Failed to send otp', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/otp/send/forgot-pin',
        summary: 'Send forgot-PIN OTP',
        description: 'Sends a one-time-password to the authenticated user\'s email, required to confirm /auth/pin/reset.',
        security: [['bearerAuth' => []]],
        tags: ['OTP'],
        responses: [
            new OA\Response(response: 200, description: 'OTP sent successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Sending failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function sendForgotPin(Request $request): JsonResponse
    {
        try {
            $otp = $this->otpService->sendForgotPin($request->bearerToken());

            return response()->json(new BaseResponse(200, 'OTP sent successfully', $otp), 200);
        } catch (SecurityException $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(404, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to send otp', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(new BaseResponse(500, 'Failed to send otp', $e->getMessage()), 500);
        }
    }
}
