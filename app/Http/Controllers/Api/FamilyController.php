<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleFamily;
use App\Exceptions\FamilyException;
use App\Helpers\EncryptionHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\Family\FamilyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FamilyController extends Controller
{

    protected FamilyService $familyService;

    public function __construct(FamilyService $familyService)
    {
        $this->familyService = $familyService;
    }

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

        $familyMember = FamilyMember::where('user', $current_user->id);

        if ($familyMember->count() > 0){
            return response()->json(new BaseResponse(400, "Users cannot create more than one family."), 400);
        }

        try {
            DB::beginTransaction();

            $families = $this->familyService->createFamily(
                token: $request->bearerToken(),
                name: $request->name
            );

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family created successfully.',
                $families
            ));
        } catch (FamilyException $e) {
            DB::rollBack();
            Log::error("Failed to create family: " . $e->getMessage());
            return response()->json(new BaseResponse(400,  $e->getMessage()), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to create family: " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to create families", $e->getMessage()), 500);
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
        $isAdmin = FamilyMember::where('user', $current_user->id)
            ->where('role', RoleFamily::Admin);

        if ($isAdmin->count() < 1){
            return response()->json(new BaseResponse(403, "User not have permission to authorize another user to family!"), 403);
        }

        try {
            DB::beginTransaction();

            $familyMember = FamilyMember::where('family', $id)
                ->where('user', $users->id);

            /**
             * Check if user already have access, but want change role
             */
            if ($familyMember->count() > 0){
                $familyMember->update([
                    'role' => $request->role
                ]);
                $familyMember = $familyMember->first();
            } else {
                $familyMember = FamilyMember::create([
                    'user' => $users->id,
                    'family' => $id,
                    'role' => $request->role
                ]);
            }

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family authorized successfully.',
                [
                    'id' => $family->id,
                    'role' => $familyMember->role,
                ]
            ));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(409, "Failed to authorized new member to family", $e->getMessage()), 409);
        }
    }

    public function deauthorized(Request $request, string $id)
    {
        $current_user = $request->user();

        $validator = Validator::make($request->all(), [
            'user_id' => ['required']
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

        try {
            DB::beginTransaction();

            $familyMember = FamilyMember::where('family', $id)
                ->where('user', $users->id);

            if ($familyMember->count() > 0){
                $familyMember->delete();
            }

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family deauthorized successfully.'
            ));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(409, "Failed to deauthorized new member to family", $e->getMessage()), 409);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        /**
         * Prepare for decrypt family data
         */
        $secretKey = $request->header('family_secret_key');
        $salt = EncryptionHelper::getUsersSalt($secretKey);
        $secretKeySanitize = str_replace("-", "", $secretKey);
        $secretKeyAsArray = explode("-", $secretKey);
        $encryptKey = $salt.$secretKeyAsArray[1].$secretKeySanitize;

        /**
         * Prepare for decrypt system data
         */
        $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");

        $family = Family::where('id', $id)
            ->with([
                'members:id,user,role,family', // Memilih field dari tabel `family_members`
                'members.users:id,name,email' // Memilih field dari tabel `users` melalui relasi `members`
            ])
            ->first();

        return response()->json(new BaseResponse(
            200,
            'Success get family details.',
            [
                'id' => $family->id,
                'name' => EncryptionHelper::decryptFromString(
                    encryptedData: $family->name,
                    key: $encryptKey
                ),
                'members' => $family->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'user_id' => $member->users->id,
                        'name' => $member->users->name,
                        'email' => $member->users->email,
                        'role' => $member->role,
                    ];
                }),
            ]
        ));
    }

    public function leave(Request $request, string $id)
    {
        /**
         * Check is family exist?
         */
        $family = Family::find($id);

        if (!$family){
            return response()->json(new BaseResponse(404, "Family not found!"), 404);
        }

        try {
            $familyMember = FamilyMember::where('family', $id);
            $count = $familyMember->count();

            if (($count - 1) == 0){
                return response()->json(new BaseResponse(409, "Failed to leave from family because no family member left!"), 409);
            }

            $familyMember = FamilyMember::where('family', $id)
                ->where('user', $request->user()->id);

            $familyMember->delete();

            return response()->json(new BaseResponse(
                201,
                'Success leave from family.'
            ));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(409, "Failed to leave from family", $e->getMessage()), 409);
        }
    }

    public function getMember(Request $request, string $id)
    {

    }

    public function getAdmin(Request $request, string $id)
    {

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
