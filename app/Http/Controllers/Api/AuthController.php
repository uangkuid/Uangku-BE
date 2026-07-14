<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleWallet;
use App\Exceptions\AuthException;
use App\Exceptions\SecurityException;
use App\Exceptions\SessionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\UserConfig\UserConfigService;
use App\Services\UserSession\UserSessionService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private AuthService $authService;

    private UserSessionService $userSessionService;

    private WalletService $walletService;

    private UserConfigService $userConfigService;

    public function __construct(
        AuthService $authService,
        UserSessionService $userSessionService,
        WalletService $walletService,
        UserConfigService $userConfigService
    ) {
        $this->authService = $authService;
        $this->userSessionService = $userSessionService;
        $this->walletService = $walletService;
        $this->userConfigService = $userConfigService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * Zero-Knowledge contract: the client has already generated the secret key,
     * derived the 2SKD unlockKey/authKey, generated the RSA keypair, and wrapped
     * the private key. The server never sees the password, the secret key, or
     * the private key in plaintext. See docs/encryption_refactor.md.
     */
    #[OA\Post(
        path: '/auth/register',
        summary: 'Register a new account',
        description: 'Zero-knowledge registration: the client has already generated the secret key, derived the '
            .'2SKD unlockKey/authKey, generated the RSA keypair, and wrapped the private key. Requires an OTP '
            .'obtained from /otp/send/register. Optionally creates a first wallet.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'otp', 'uuid', 'salt', 'auth_key', 'public_key', 'wrapped_private_key'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                    new OA\Property(property: 'uuid', type: 'string'),
                    new OA\Property(property: 'salt', type: 'string', description: 'Client-generated KDF salt'),
                    new OA\Property(property: 'auth_key', type: 'string', description: 'Client-derived authentication key (never the raw password)'),
                    new OA\Property(property: 'public_key', type: 'string', description: 'Client-generated RSA public key'),
                    new OA\Property(property: 'wrapped_private_key', type: 'string', description: 'RSA private key wrapped under the unlockKey'),
                    new OA\Property(property: 'wallet_name', type: 'string', nullable: true),
                    new OA\Property(property: 'wallet_amount', type: 'string', nullable: true),
                    new OA\Property(property: 'start_date_month', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Account created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')
            ),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 409, description: 'Account already exists / registration failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'otp' => 'required|min:6|max:6',
            'uuid' => 'required',
            'salt' => 'required|string',
            'auth_key' => 'required|string',
            'public_key' => 'required|string',
            'wrapped_private_key' => 'required|string',
            'wallet_name' => 'nullable|string',
            'wallet_amount' => 'nullable|string',
            'start_date_month' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to create account', $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();

            $registerResult = $this->authService->register(
                name: $request->name,
                email: $request->email,
                authKey: $request->auth_key,
                salt: $request->salt,
                publicKey: $request->public_key,
                wrappedPrivateKey: $request->wrapped_private_key,
                otp: $request->otp,
                uuid: $request->uuid,
            );

            $user = $registerResult['user'];

            $this->userConfigService->create([
                'users' => $user->id,
                'is_pin_enabled' => false,
                'start_date_month' => $request->start_date_month,
            ]);

            if ($request->filled('wallet_name') && $request->filled('wallet_amount')) {
                $wallet = $this->walletService->create([
                    'name' => $request->wallet_name,
                    'amount' => $request->wallet_amount,
                    'created_by' => $user->id,
                ]);

                $this->walletService->grantAccess(
                    userId: $user->id,
                    walletId: $wallet->id,
                    accessType: RoleWallet::Admin
                );
            }

            $this->userSessionService->create([
                'refresh_token' => $registerResult['refresh_token'],
                'users' => $user->id,
            ]);

            DB::commit();

            return response()->json(new BaseResponse(201, 'Account created successfully', [
                'id' => $user->id,
                'token' => $registerResult['token'],
                'refresh_token' => $registerResult['refresh_token'],
            ]), 201);
        } catch (AuthException $e) {
            DB::rollBack();
            Log::error('Failed create account '.$e->getMessage());

            return response()->json(new BaseResponse(409, $e->getMessage(), null), 409);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed create account '.$e->getMessage());

            return response()->json(new BaseResponse(409, 'Failed to create account '.$e->getMessage(), null), 409);
        }
    }

    /**
     * Challenge-based login: client derives authKey locally from email + salt
     * (fetched via /auth/salt) + password + secret key. The server never
     * receives the password or the secret key.
     */
    #[OA\Post(
        path: '/auth/login',
        summary: 'Login',
        description: 'Challenge-based login: the client derives auth_key locally from email + salt (fetched via '
            .'/auth/salt) + password + secret key. Rate limited to 10 requests/min.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'auth_key'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'auth_key', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed / invalid credentials', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'auth_key' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to login', $validator->errors()), 400);
        }

        try {
            $loginResult = $this->authService->login(
                email: $request->email,
                authKey: $request->auth_key
            );
            $user = $loginResult['user'];
            $userKey = $loginResult['user_key'];

            $this->userSessionService->create([
                'users' => $user->id,
                'refresh_token' => $loginResult['refresh_token'],
            ]);

            return response()->json(new BaseResponse(
                200,
                'Login successful',
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $loginResult['avatar'],
                    'token' => $loginResult['token'],
                    'refresh_token' => $loginResult['refresh_token'],
                    'public_key' => $userKey->public_key,
                    'wrapped_private_key' => $userKey->private_key,
                ]
            ), 200);
        } catch (AuthException $e) {
            Log::error('Failed to login '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            Log::error('Failed to login '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to login '.$e->getMessage(), null), 500);
        }
    }

    /**
     * Return the salt (and KDF iteration count) a client needs to derive its
     * authKey for a given email, without revealing whether the account exists.
     */
    #[OA\Post(
        path: '/auth/salt',
        summary: 'Get login salt',
        description: 'Returns the salt (and KDF iteration count) needed to derive auth_key for a given email, '
            .'without revealing whether the account exists. Rate limited to 20 requests/min.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function salt(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to get salt', $validator->errors()), 400);
        }

        try {
            $result = $this->authService->getSalt($request->email);

            return response()->json(new BaseResponse(200, 'Success', $result), 200);
        } catch (Exception $e) {
            Log::error('Failed to get salt '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to get salt', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout',
        description: 'Revokes the given refresh token and blacklists the current access token.',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [new OA\Property(property: 'refresh_token', type: 'string')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Logout successful', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 401, description: 'Invalid/expired session', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Logout failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        // set validation
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to logout', $validator->errors()), 400);
        }

        try {

            $this->userSessionService->revokeSession($request->refresh_token);

            $logout = $this->authService->logout(
                token: $request->bearerToken(),
                refreshToken: $request->refresh_token
            );

            if ($logout) {
                return response()->json(
                    new BaseResponse(
                        200,
                        'Logout has been successfully',
                        null
                    )
                );
            } else {
                return response()->json(
                    new BaseResponse(
                        500,
                        'Logout failed',
                        null
                    ),
                    500
                );
            }
        } catch (SessionException $e) {
            Log::error('Failed to logout '.$e->getMessage());

            return response()->json(new BaseResponse(401, $e->getMessage(), null), 401);
        } catch (Exception $e) {
            Log::error('Failed to logout '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to logout '.$e->getMessage(), null), 500);
        }
    }

    #[OA\Post(
        path: '/auth/refresh-token',
        summary: 'Refresh access token',
        description: 'Revokes the given refresh token and session, then issues a new access + refresh token pair.',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [new OA\Property(property: 'refresh_token', type: 'string')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refresh token successful', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 401, description: 'Refresh token expired or invalid', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function refreshToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to refresh token', $validator->errors()), 400);
        }

        try {
            $revokedUser = $this->userSessionService->revokeSession($request->refresh_token);

            // Revoke all token access
            $logout = $this->authService->logout(
                token: $request->bearerToken(),
                refreshToken: $request->refresh_token
            );

            $newAccessToken = auth()->login($revokedUser);
            $newRefreshToken = $this->generateRefreshToken($revokedUser);

            // Save to user seasons
            $this->userSessionService->create([
                'refresh_token' => $newRefreshToken,
                'users' => $revokedUser->id,
            ]);

            if ($logout) {
                return response()->json(new BaseResponse(
                    200,
                    'Refresh token successful',
                    [
                        'token' => $newAccessToken,
                        'refresh_token' => $newRefreshToken,
                    ]
                ), 200);
            } else {
                return response()->json(
                    new BaseResponse(
                        500,
                        'Logout failed',
                        null
                    ),
                    500
                );
            }
        } catch (TokenExpiredException $e) {
            Log::error('Failed to refresh token '.$e->getMessage());

            return response()->json(new BaseResponse(401, 'Refresh token expired'), 401);
        } catch (JWTException $e) {
            Log::error('Failed to refresh token '.$e->getMessage());

            return response()->json(new BaseResponse(401, 'Invalid refresh token'), 401);
        } catch (SessionException $e) {
            Log::error('Failed to refresh token '.$e->getMessage());

            return response()->json(new BaseResponse(401, $e->getMessage(), null), 401);
        } catch (Exception $e) {
            Log::error('Failed to refresh token '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to refresh token '.$e->getMessage(), null), 500);
        }
    }

    protected function generateRefreshToken(User $user)
    {
        // Set TTL untuk refresh token menjadi lebih panjang (misalnya 7 hari)
        // Set masa berlaku refresh token secara manual (contoh: 7 hari)
        $customClaims = ['exp' => now()->addDays(7)->timestamp];

        // Buat refresh token dengan masa berlaku yang lebih panjang
        $refreshToken = JWTAuth::claims($customClaims)->fromUser($user);

        return $refreshToken;
    }

    #[OA\Post(
        path: '/auth/pre-register',
        summary: 'Pre-register email check',
        description: 'Checks whether an email is eligible for registration, before sending the registration OTP.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pre-register success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed / email not eligible', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function preRegister(Request $request): JsonResponse
    {
        // set validation
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to pre-register', $validator->errors()), 400);
        }

        try {
            $this->authService->preRegister($request->email);

            return response()->json(new BaseResponse(
                status: 200,
                message: 'Pre-register success',
                resource: [
                    'email' => $request->email,
                ]
            ));
        } catch (AuthException $e) {
            Log::error('Failed to pre-register '.$e->getMessage(), [
                'email' => $request->email,
            ]);

            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            Log::error('Failed to pre-register '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to pre-register '.$e->getMessage(), null), 500);
        }
    }

    #[OA\Post(
        path: '/auth/pre-change-password',
        summary: 'Pre-change-password check',
        description: 'Security pre-check before sending the change-password OTP (see /otp/send/change-password).',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Pre-change password success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 423, description: 'Locked (security check failed)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function preChangePassword(Request $request): JsonResponse
    {
        try {
            $this->authService->preChangeCredentials($request->bearerToken());

            return response()->json(new BaseResponse(
                status: 200,
                message: 'Pre-change password success',
            ));
        } catch (AuthException $e) {
            Log::error('Failed to change password '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (SecurityException $e) {
            Log::error('Failed to change password '.$e->getMessage());

            return response()->json(new BaseResponse(423, $e->getMessage(), null), 423);
        } catch (Exception $e) {
            Log::error('Failed to change password '.$e->getMessage());

            return response()->json(new BaseResponse(409, 'Failed to change password '.$e->getMessage(), null), 409);
        }
    }

    /**
     * Change password and/or secret key while authenticated. The client still
     * holds the old unlockKey, decrypts the existing private key locally, and
     * re-wraps it under the new unlockKey — no data loss.
     */
    #[OA\Post(
        path: '/auth/change-password',
        summary: 'Change password / secret key',
        description: 'Changes password and/or secret key while authenticated. The client decrypts the existing '
            .'private key locally with the old unlockKey and re-wraps it under the new unlockKey. Requires an OTP '
            .'obtained from /otp/send/change-password. Revokes all other sessions on success.',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['old_auth_key', 'new_salt', 'new_auth_key', 'new_wrapped_private_key', 'otp', 'uuid'],
                properties: [
                    new OA\Property(property: 'old_auth_key', type: 'string'),
                    new OA\Property(property: 'new_salt', type: 'string'),
                    new OA\Property(property: 'new_auth_key', type: 'string'),
                    new OA\Property(property: 'new_wrapped_private_key', type: 'string'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                    new OA\Property(property: 'uuid', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Change password success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 409, description: 'Failed to change credentials', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'old_auth_key' => 'required|string',
            'new_salt' => 'required|string',
            'new_auth_key' => 'required|string',
            'new_wrapped_private_key' => 'required|string',
            'otp' => 'required|min:6|max:6',
            'uuid' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to change credentials', $validator->errors()), 400);
        }

        try {
            $user = $this->authService->changeCredentials(
                token: $request->bearerToken(),
                oldAuthKey: $request->old_auth_key,
                newSalt: $request->new_salt,
                newAuthKey: $request->new_auth_key,
                newWrappedPrivateKey: $request->new_wrapped_private_key,
                otp: $request->otp,
                uuid: $request->uuid
            );

            $this->userSessionService->revokeAllSession($user);

            return response()->json(new BaseResponse(
                200,
                'Change password success',
            ));
        } catch (AuthException $e) {
            Log::error('Failed to change password '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            Log::error('Failed to change password '.$e->getMessage());

            return response()->json(new BaseResponse(409, 'Failed to change password '.$e->getMessage(), null), 409);
        }
    }

    #[OA\Post(
        path: '/auth/forgot-password',
        summary: 'Request password reset',
        description: 'Triggers the forgot-password flow for the given email. Follow with /otp/send/forgot-password then /auth/reset-password.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Forgot password success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        // set validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to forgot password', $validator->errors()), 400);
        }

        try {
            $this->authService->forgotPassword($request->email);

            return response()->json(new BaseResponse(
                status: 200,
                message: 'Forgot password success',
                resource: [
                    'email' => $request->email,
                ]
            ));
        } catch (AuthException $e) {
            Log::error('Failed to forgot password '.$e->getMessage(), [
                'email' => $request->email,
            ]);

            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            Log::error('Failed to forgot password '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to forgot password '.$e->getMessage(), null), 500);
        }
    }

    /**
     * Recover account access after a forgotten password. Since the client
     * cannot unwrap the old private key without the old password, this
     * replaces the account's key material with a brand new keypair — any
     * data encrypted under the old key becomes unreadable afterwards.
     */
    #[OA\Post(
        path: '/auth/reset-password',
        summary: 'Reset password (forgot password recovery)',
        description: 'Recovers account access after a forgotten password. Since the client cannot unwrap the old '
            .'private key, this replaces the account key material with a brand new keypair — data encrypted under '
            .'the old key becomes unreadable afterwards. Requires an OTP obtained from /otp/send/forgot-password. '
            .'Revokes all sessions on success.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp', 'uuid', 'new_salt', 'new_auth_key', 'new_public_key', 'new_wrapped_private_key'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                    new OA\Property(property: 'uuid', type: 'string'),
                    new OA\Property(property: 'new_salt', type: 'string'),
                    new OA\Property(property: 'new_auth_key', type: 'string'),
                    new OA\Property(property: 'new_public_key', type: 'string'),
                    new OA\Property(property: 'new_wrapped_private_key', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reset password success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|min:6|max:6',
            'uuid' => 'required',
            'new_salt' => 'required|string',
            'new_auth_key' => 'required|string',
            'new_public_key' => 'required|string',
            'new_wrapped_private_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to reset password', $validator->errors()), 400);
        }

        try {
            $user = $this->authService->resetCredentials(
                email: $request->email,
                newSalt: $request->new_salt,
                newAuthKey: $request->new_auth_key,
                newPublicKey: $request->new_public_key,
                newWrappedPrivateKey: $request->new_wrapped_private_key,
                otp: $request->otp,
                uuid: $request->uuid
            );

            $this->userSessionService->revokeAllSession($user);

            return response()->json(new BaseResponse(
                status: 200,
                message: 'Reset password success',
                resource: null
            ));
        } catch (AuthException $e) {
            Log::error('Failed to reset password '.$e->getMessage(), [
                'email' => $request->email,
            ]);

            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            Log::error('Failed to reset password '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to reset password '.$e->getMessage(), null), 500);
        }
    }
}
