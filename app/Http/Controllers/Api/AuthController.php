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
