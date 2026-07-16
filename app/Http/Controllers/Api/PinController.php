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
use OpenApi\Attributes as OA;

class PinController extends Controller
{
    protected PinService $pinService;

    public function __construct(PinService $pinService)
    {
        $this->pinService = $pinService;
    }

    #[OA\Post(
        path: '/auth/pin',
        summary: 'Create transaction PIN',
        description: 'Creates a 6-digit PIN for the authenticated user. Requires an OTP obtained from /otp/send/pin.',
        security: [['bearerAuth' => []]],
        tags: ['PIN'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pin', 'pin_confirmation', 'uuid', 'otp'],
                properties: [
                    new OA\Property(property: 'pin', type: 'string', example: '123456', description: '6-digit numeric PIN'),
                    new OA\Property(property: 'pin_confirmation', type: 'string', example: '123456'),
                    new OA\Property(property: 'uuid', type: 'string'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Create PIN success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed / PIN already enabled', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'pin' => 'required|confirmed|numeric|digits:6',
            'uuid' => 'required',
            'otp' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed create PIN', $validator->errors()), 400);
        }

        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if ($isPinEnable) {
                return response()->json(new BaseResponse(400, 'PIN is already enabled'), 400);
            }

            $this->pinService->createPin(
                token: $request->bearerToken(),
                pin: $request->pin,
                uuid: $request->uuid,
                otp: $request->otp
            );

            return response()->json(new BaseResponse(200, 'Create PIN success'), 200);
        } catch (AuthException|PinException $e) {
            Log::error($e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed create PIN', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/auth/pin/init',
        summary: 'Initialize PIN flow',
        security: [['bearerAuth' => []]],
        tags: ['PIN'],
        responses: [
            new OA\Response(response: 200, description: 'Init pin success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function init(Request $request): JsonResponse
    {
        try {
            $this->pinService->initPin($request->bearerToken());

            return response()->json(new BaseResponse(200, 'Init pin success'), 200);
        } catch (PinException $e) {
            Log::error('Failed init PIN '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed init PIN '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed init PIN', $e->getMessage()), 500);
        }
    }

    #[OA\Delete(
        path: '/auth/pin',
        summary: 'Delete transaction PIN',
        security: [['bearerAuth' => []]],
        tags: ['PIN'],
        responses: [
            new OA\Response(response: 200, description: 'Delete PIN success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'PIN already disabled', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if (! $isPinEnable) {
                return response()->json(new BaseResponse(400, 'PIN is already disabled'), 400);
            }

            $this->pinService->deletePin($request->bearerToken());

            return response()->json(new BaseResponse(200, 'Delete PIN success'), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error('Failed delete PIN '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed delete PIN '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed delete PIN', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/auth/pin/forgot',
        summary: 'Forgot PIN (while password known)',
        description: 'Disables/resets PIN state using the current auth_key, for a user who knows their password but forgot their PIN.',
        security: [['bearerAuth' => []]],
        tags: ['PIN'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['auth_key'],
                properties: [new OA\Property(property: 'auth_key', type: 'string')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Forgot PIN success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed / PIN disabled', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function forgot(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'auth_key' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed forgot PIN', $validator->errors()), 400);
        }

        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if (! $isPinEnable) {
                return response()->json(new BaseResponse(400, 'PIN is currently disabled'), 400);
            }

            $this->pinService->forgotPin(
                token: $request->bearerToken(),
                authKey: $request->auth_key
            );

            return response()->json(new BaseResponse(200, 'Forgot PIN success'), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error('Failed forgot PIN '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed forgot PIN '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed forgot PIN', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/auth/pin/reset',
        summary: 'Reset PIN',
        description: 'Sets a new PIN. Requires an OTP obtained from /otp/send/forgot-pin.',
        security: [['bearerAuth' => []]],
        tags: ['PIN'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pin', 'pin_confirmation', 'uuid', 'otp'],
                properties: [
                    new OA\Property(property: 'pin', type: 'string', example: '123456'),
                    new OA\Property(property: 'pin_confirmation', type: 'string', example: '123456'),
                    new OA\Property(property: 'uuid', type: 'string'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reset PIN success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed / PIN disabled', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reset(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'pin' => 'required|confirmed|numeric|digits:6',
            'uuid' => 'required',
            'otp' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed reset PIN', $validator->errors()), 400);
        }

        try {
            $isPinEnable = $this->pinService->isPinEnable($request->bearerToken());

            if (! $isPinEnable) {
                return response()->json(new BaseResponse(400, 'PIN is currently disabled'), 400);
            }

            $this->pinService->resetPin(
                token: $request->bearerToken(),
                pin: $request->pin,
                uuid: $request->uuid,
                otp: $request->otp
            );

            return response()->json(new BaseResponse(200, 'Reset PIN success'), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error('Failed reset PIN '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed reset PIN '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed reset PIN', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/auth/pin/verify',
        summary: 'Verify PIN',
        description: 'Verifies the given PIN against the authenticated user\'s stored PIN. Rate limited to 5 requests/min.',
        security: [['bearerAuth' => []]],
        tags: ['PIN'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pin'],
                properties: [new OA\Property(property: 'pin', type: 'string', example: '123456')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Verify PIN success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed / PIN disabled / incorrect PIN', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function verify(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'pin' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed verify PIN', $validator->errors()), 400);
        }

        try {
            $isEnable = $this->pinService->isPinEnable($request->bearerToken());

            if (! $isEnable) {
                return response()->json(new BaseResponse(400, 'PIN is currently disabled'), 400);
            }

            $this->pinService->verifyPin(
                token: $request->bearerToken(),
                pin: $request->pin
            );

            return response()->json(new BaseResponse(200, 'Verify PIN success'), 200);
        } catch (AuthException|SecurityException $e) {
            Log::error('Failed verify PIN '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed verify PIN '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed verify PIN', $e->getMessage()), 500);
        }
    }
}
