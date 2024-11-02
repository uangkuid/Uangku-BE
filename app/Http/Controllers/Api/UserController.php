<?php

namespace App\Http\Controllers\Api;

use App\Helpers\EncryptionHelper;
use App\Http\Controllers\Controller;
use App\Models\UserSeasons;
use App\Models\Wallet;
use App\Models\WalletAccess;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\BaseResponse;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(new BaseResponse(200, "User data", auth()->guard('api')->user()), 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
            'password' => 'required|min:8|confirmed'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create account", $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();

            /**
             * Prepare data before create account
             */
            $secretKey = EncryptionHelper::generateUsersSecretKey();
            $salt = EncryptionHelper::getUsersSalt($secretKey);
            $secretKeySanitize = str_replace("-", "", $secretKey);
            $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
            $password = $request->password;
            $encryptKey = $salt.$password.$secretKeySanitize;
            $encryptedEmail = EncryptionHelper::encryptAsString(
                data: $request->email,
                key: EncryptionHelper::getSystemSecretKey(),
                iv: $staticIv,
            );

            /**
             * Find Existing Users
             */
            $user = User::where('email', $encryptedEmail);

            //Throw error when email already taken
            if ($user->count() > 0) {
                return response()->json(new BaseResponse(409, "Failed to create account ", [
                    "email" => "Email already taken!"
                ]), 409);
            }

            /**
             * Create Account
             */
            $user = User::create([
                'name' => EncryptionHelper::encryptAsString(
                    data: $request->name,
                    key: EncryptionHelper::getSystemSecretKey(),
                    iv: $staticIv,
                ),
                'email' => $encryptedEmail,
                'password' => bcrypt($encryptKey)
            ]);

            $wallet_name = sprintf("%s's Cash", $request->name);

            /**
             * Create users wallet
             */
            $wallet = Wallet::create([
                'name' => EncryptionHelper::encryptAsString(
                    data: $wallet_name,
                    key: $encryptKey
                ),
                'amount' => EncryptionHelper::encryptAsString(
                    data: "0",
                    key: $encryptKey
                ),
            ]);

            /**
             * Grant users access to wallet
             */
            $walletAccess = WalletAccess::create([
                'users' => $user->id,
                'wallets' => $wallet->id,
                'is_active' => true,
                'role' => 'Admin'
            ]);

            $accessToken = auth()->login($user);
            $refreshToken = $this->generateRefreshToken($user);

            /**
             * Save users to seasons
             */
            UserSeasons::create([
                'refresh_token' => $refreshToken,
                'users' => $user->id
            ]);

            DB::commit();

            return response()->json(new BaseResponse(201, "Account created successfully", [
                "name" => $request->name,
                "email" => $request->email,
                "secret_key" => $secretKey,
                'wallet' => [
                    "id" => $wallet->id,
                    "name" => $wallet_name,
                    "amount" => "0",
                ],
                'token' => $accessToken,
                'refresh_token' => $refreshToken
            ]), 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(409, "Failed to create account " . $e->getMessage(), null), 409);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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

        /**
         * Prepare data before auth
         */
        $secretKey = $request->secret_key;
        $salt = EncryptionHelper::getUsersSalt($secretKey);
        $secretKeySanitize = str_replace("-", "", $secretKey);
        $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
        $password = $request->password;
        $encryptKey = $salt.$password.$secretKeySanitize;

        $encryptedEmail = EncryptionHelper::encryptAsString(
            data: $request->email,
            key: EncryptionHelper::getSystemSecretKey(),
            iv: $staticIv,
        );

        //get credentials from request
//        $credentials = $request->only('email', 'password');
        $credentials = [
            'email' => $encryptedEmail,
            'password' => $encryptKey,
        ];

        //if auth failed
        if (!$token = auth()->guard('api')->attempt($credentials)) {
            return response()->json(new BaseResponse(
                401,
                "Unauthorized",
                null
            ), 401);
        }

        $user = auth()->guard('api')->user();
        $refresh_token = $this->generateRefreshToken($user);

        //Save to user seasons
        UserSeasons::create([
            'refresh_token' => $refresh_token,
            'users' => $user->id
        ]);

        return response()->json(new BaseResponse(
            200,
            "Login successful",
            [
                'id' => $user->id,
                'name' => EncryptionHelper::decryptFromString($user->name, EncryptionHelper::getSystemSecretKey()),
                'avatar' => $user->avatar,
                'token' => $token,
                'refresh_token' => $refresh_token
            ]
        ), 200);
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

        if(!$userSeasons) {
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

    public function refreshToken(Request $request)
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

            if(!$userSeasons) {
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
}
