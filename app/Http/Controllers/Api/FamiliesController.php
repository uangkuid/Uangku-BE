<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleFamily;
use App\Helpers\EncryptionHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FamiliesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

        $familyMember = FamilyMember::where('users', $current_user->id);

        if ($familyMember->count() > 0){
            return response()->json(new BaseResponse(400, "Users cannot create more than one family."), 400);
        }

        try {
            DB::beginTransaction();

            $secretKey = EncryptionHelper::generateUsersSecretKey();
            $salt = EncryptionHelper::getUsersSalt($secretKey);
            $secretKeySanitize = str_replace("-", "", $secretKey);

            $families = Family::create();

            $secretKeyAsArray = explode("-", $secretKey);

            $encryptKey = $salt.$secretKeyAsArray[1].$secretKeySanitize;

            $families->update([
                'name' => EncryptionHelper::encryptAsString(
                    data: $request->name,
                    key: $encryptKey
                ),
            ]);

            $familyMember = FamilyMember::create([
                'users' => $current_user->id,
                'family' => $families->id,
                'role' => RoleFamily::Admin
            ]);

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family created successfully.',
                [
                    'id' => $families->id,
                    'name' => $request->name,
                    'role' => $familyMember->role,
                    'secret_key' => $secretKey
                ]
            ));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(409, "Failed to create families", $e->getMessage()), 409);
        }
    }

    public function authorized(Request $request, string $id)
    {
        $current_user = $request->user();

        $validator = Validator::make($request->all(), [
            'user_id' => ['required'],
            'role' => ['required', Rule::enum(RoleFamily::class)],
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to authorized users to family", $validator->errors()), 400);
        }

        /**
         * Check is family exist?
         */
        $family = Family::find($id);

        if (!$family){
            return response()->json(new BaseResponse(404, "Family not found!"), 404);
        }

        $users = User::find($request->user_id);

        if (!$users){
            return response()->json(new BaseResponse(404, "User not found!"), 404);
        }

        /**
         * Check is user eligible to authorize another user to family?
         */
        $isAdmin = FamilyMember::where('users', $current_user->id)
            ->where('role', RoleFamily::Admin);

        if ($isAdmin->count() < 1){
            return response()->json(new BaseResponse(403, "User not have permission to authorize another user to family!"), 403);
        }

        try {
            DB::beginTransaction();

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family authorized successfully.',
                [
                ]
            ));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(409, "Failed to authorized new member to family", $e->getMessage()), 409);
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
