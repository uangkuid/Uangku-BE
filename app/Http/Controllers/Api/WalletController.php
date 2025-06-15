<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\Wallet;
use App\Services\User\UserService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{

    protected WalletService $walletService;
    protected UserService $userService;

    public function __construct(WalletService $walletService, UserService $userService)
    {
        $this->walletService = $walletService;
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $current_user = $request->user();

        $wallet = Wallet::whereHas('access', function ($query) use ($current_user) {
            $query->where('users', $current_user->id);
        })->get();

        return response()->json(new BaseResponse(200, "Wallet data", $wallet));
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
        $current_user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create families", $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();

            $user = $this->userService->getUserByToken($request->bearerToken());

            $wallet = $this->walletService->createWallet(
                name: $request->name,
                userId: $user->id,
                familyId: $request->family_id ?? null,
            );

//            $secret_key = $request->personal_secret_key;
//            $familyKey = $request->family_secret_key;
//
//            if(!empty($familyKey) && $familyKey != null) {
//                $secret_key = $familyKey;
//            }
//
//            $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
//            $name = EncryptionHelper::encryptAsString(
//                data: $request->name,
//                key: $secret_key,
//                iv: $staticIv
//            );
//            $amount = EncryptionHelper::encryptAsString(
//                data: "0",
//                key: $secret_key,
//                iv: $staticIv
//            );
//
//            if ($familyKey == $secret_key) {
//                $wallet = Wallet::where(['name' => $name])
//                    ->where(['families' => $request->family_id])
//                    ->limit(1);
//
//                if ($wallet->count() > 0) {
//                    return response()->json(new BaseResponse(400, $request->name . " has already."), 400);
//                }
//
//                $wallet = Wallet::create([
//                    "name" => $name,
//                    "amount" => $amount,
//                    "created_by" => $current_user->id,
//                    "families" => $request->family_id
//                ]);
//
//                $familyMember = FamilyMember::where('family', $request->family_id)
//                    ->where('user', '!=', $current_user->id)
//                    ->get();
//
//                foreach ($familyMember as $member) {
//                    $walletAccess = WalletAccess::create([
//                        'users' => $member->user,
//                        'wallets' => $wallet->id,
//                        'is_active' => true,
//                        'role' => RoleWallet::Member
//                    ]);
//                }
//            } else {
//                //Find if user has created with same wallet name
//                $wallet = Wallet::where(['name' => $name])
//                    ->where(['created_by' => $current_user->id])
//                    ->limit(1);
//
//                if ($wallet->count() > 0) {
//                    return response()->json(new BaseResponse(400, $request->name . " has already."), 400);
//                }
//
//                $wallet = Wallet::create([
//                    "name" => $name,
//                    "amount" => $amount,
//                    "created_by" => $current_user->id,
//                ]);
//            }
//
//            $walletAccess = WalletAccess::create([
//                'users' => $current_user->id,
//                'wallets' => $wallet->id,
//                'is_active' => true,
//                'role' => RoleWallet::Admin
//            ]);

//            dd($wallet);

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family created successfully.',
                [
                    "id" => $wallet["wallet"]->id,
                    "name" => $request->name,
                    "amount" => "0",
//                    "role" => [
//                        "role" => $walletAccess->role,
//                        "is_active" => $walletAccess->is_active,
//                    ]
                ]
            ));
        } catch (FamilyException|GeneralException|UserException $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(500, "Failed to create wallet", $e->getMessage()), 500);
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
}
