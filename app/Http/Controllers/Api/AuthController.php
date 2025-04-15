<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleWallet;
use App\Exceptions\AuthException;
use App\Helpers\EncryptionHelper;
use App\Helpers\TokenHelper;
use App\Http\Controllers\Controller;
use App\Models\UserSeasons;
use App\Services\Auth\AuthService;
use App\Services\UserSession\UserSessionService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\BaseResponse;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private AuthService $authService;
    private UserSessionService $userSessionService;
    private WalletService $walletService;

    public function __construct(
        AuthService        $authService,
        UserSessionService $userSessionService,
        WalletService      $walletService
    )
    {
        $this->authService = $authService;
        $this->userSessionService = $userSessionService;
        $this->walletService = $walletService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(new BaseResponse(200, "User data", auth()->guard('api')->user()), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //Set validation
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'otp' => 'required|min:6|max:6',
            'uuid' => 'required',
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create account", $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();

            /**
             * Create Account
             */
            $registerResult = $this->authService->register(
                name: $request->name,
                email: $request->email,
                password: $request->password,
                otp: $request->otp,
                uuid: $request->uuid,
            );

            $user = $registerResult["user"];

            /**
             * Save User Key
             */
            $userKey = $this->authService->saveUserKey(
                userId: $user->id,
                publicKey: $registerResult['public_key'],
                privateKey: $registerResult['private_key'],
                secretKey: $registerResult['secret_key'],
                password: $request->password
            );

            $wallet_name = sprintf("%s's Cash", $request->name);

            /**
             * Create users wallet
             */
            $wallet = $this->walletService->create([
                'name' => EncryptionHelper::encryptAsymmetric($wallet_name, $registerResult['raw_public_key']),
                'amount' => EncryptionHelper::encryptAsymmetric("0", $registerResult['raw_public_key']),
                'created_by' => $user->id,
            ]);

            /**
             * Grant users access to wallet
             */
            $walletAccess = $this->walletService->grantAccess(
                userId: $user->id,
                walletId: $wallet->id,
                accessType: RoleWallet::Admin
            );

            /**
             * Save users to seasons
             */
            $this->userSessionService->create([
                'refresh_token' => $registerResult['refresh_token'],
                'users' => $user->id
            ]);

            DB::commit();

            return response()->json(new BaseResponse(201, "Account created successfully", [
                "id" => $user->id,
                "name" => $request->name,
                "email" => $request->email,
                "secret_key" => $registerResult['secret_key'],
                'wallet' => [
                    "id" => $wallet->id,
                    "name" => $wallet_name,
                    "amount" => "0",
                    'role' => $walletAccess->role
                ],
                'token' => $registerResult['token'],
                'refresh_token' => $registerResult['refresh_token'],
                'public_key' => $userKey->public_key,
                'private_key' => $userKey->private_key,
            ]), 201);
        } catch (AuthException $e) {
            DB::rollBack();
            Log::error("Failed create account " . $e->getMessage());
            return response()->json(new BaseResponse(409, $e->getMessage(), null), 409);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed create account " . $e->getMessage());
            return response()->json(new BaseResponse(409, "Failed to create account " . $e->getMessage(), null), 409);
        }
    }

    /**
     * Method Login users
     */
    function login(Request $request): JsonResponse
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
            'secret_key' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to login", $validator->errors()), 400);
        }

        try {
            $loginResult = $this->authService->login(
                email: $request->email,
                password: $request->password,
                secretKey: $request->secret_key
            );
            $user = $loginResult['user'];
            $userKey = $this->authService->getUserKey($user->id);

            //Save to user seasons
            $this->userSessionService->create([
                'users' => $user->id,
                'refresh_token' => $loginResult["refresh_token"]
            ]);

            return response()->json(new BaseResponse(
                200,
                "Login successful",
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'token' => $loginResult['token'],
                    'refresh_token' => $loginResult["refresh_token"],
                    'public_key' => $userKey->public_key,
                    'private_key' => $userKey->private_key,
                ]
            ), 200);
        } catch (AuthException $e) {
            Log::error("Failed to login " . $e->getMessage());
            return response()->json(new BaseResponse(409, $e->getMessage(), null), 409);
        } catch (Exception $e) {
            Log::error("Failed to login " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to login " . $e->getMessage(), null), 500);
        }
    }

    function logout(Request $request): JsonResponse
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to logout", $validator->errors()), 400);
        }

        $refreshToken = $request->input('refresh_token');

        // Verifikasi refresh token
        $isExist = UserSeasons::where('refresh_token', $refreshToken)->exists();

        if (!$isExist) {
            return response()->json(new BaseResponse(401, "Invalid refresh token"), 401);
        }

        $user = JWTAuth::setToken($refreshToken)->toUser();

        if (!$user) {
            return response()->json(new BaseResponse(401, "Invalid refresh token"), 401);
        }

        $userSeasons = UserSeasons::where('refresh_token', $refreshToken)
            ->where('users', $user->id)
            ->first();

        if (!$userSeasons) {
            return response()->json(new BaseResponse(401, "Invalid refresh token"), 401);
        }

        $userSeasons->delete();

        $revokeToken = JWTAuth::invalidate(JWTAuth::getToken());
        $revokeRefreshToken = JWTAuth::invalidate($refreshToken);

        if ($revokeToken && $revokeRefreshToken) {
            return response()->json(
                new BaseResponse(
                    200,
                    "Logout has been successfully",
                    null
                )
            );
        } else {
            return response()->json(
                new BaseResponse(
                    500,
                    "Logout failed",
                    null
                ),
                500
            );
        }
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to login", $validator->errors()), 400);
        }

        $refreshToken = $request->input('refresh_token');

        try {
            // Verifikasi refresh token dan buat access token baru
            $isExist = UserSeasons::where('refresh_token', $refreshToken)->exists();

            if (!$isExist) {
                return response()->json(new BaseResponse(401, "Invalid refresh token"), 401);
            }

            $user = JWTAuth::setToken($refreshToken)->toUser();

            if (!$user) {
                return response()->json(new BaseResponse(401, "Invalid refresh token"), 401);
            }

            $userSeasons = UserSeasons::where('refresh_token', $refreshToken)
                ->where('users', $user->id)
                ->first();

            if (!$userSeasons) {
                return response()->json(new BaseResponse(401, "Invalid refresh token"), 401);
            }

            $userSeasons->delete();

            //Revoke all token access
            $removeToken = JWTAuth::invalidate(JWTAuth::getToken());
            $removeRefreshToken = JWTAuth::invalidate($refreshToken);

            $newAccessToken = auth()->login($user);
            $newRefreshToken = $this->generateRefreshToken($user);

            //Save to user seasons
            UserSeasons::create([
                'refresh_token' => $newRefreshToken,
                'users' => $user->id
            ]);

            if ($removeToken && $removeRefreshToken) {
                return response()->json(new BaseResponse(
                    200,
                    "Refresh token successful",
                    [
                        'token' => $newAccessToken,
                        'refresh_token' => $newRefreshToken
                    ]
                ), 200);
            } else {
                return response()->json(
                    new BaseResponse(
                        500,
                        "Logout failed",
                        null
                    ),
                    500
                );
            }
        } catch (TokenExpiredException $e) {
            return response()->json(new BaseResponse(401, "Refresh token expired"), 401);
        } catch (JWTException $e) {
            return response()->json(new BaseResponse(401, "Invalid refresh token"), 401);
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
        //set validation
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to pre-register", $validator->errors()), 400);
        }

        try {
            $this->authService->preRegister($request->email);

            return response()->json(new BaseResponse(
                status: 200,
                message: "Pre-register success",
                resource: [
                    'email' => $request->email,
                ]
            ));
        } catch (AuthException $e) {
            Log::error("Failed to pre-register " . $e->getMessage(), [
                'email' => $request->email
            ]);
            return response()->json(new BaseResponse(409, $e->getMessage(), null), 409);
        } catch (Exception $e) {
            Log::error("Failed to pre-register " . $e->getMessage());
            return response()->json(new BaseResponse(409, "Failed to pre-register " . $e->getMessage(), null), 409);
        }
    }
}
