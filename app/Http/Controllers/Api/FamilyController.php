<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FamilyException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\PaginationResponse;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\Family\FamilyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
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

        if ($familyMember->count() > 0) {
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
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to create family: " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to create families", $e->getMessage()), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {

    }

    public function leave(Request $request, string $id): JsonResponse
    {
        /**
         * Check is family exist?
         */
        $family = Family::find($id);

        if (!$family) {
            return response()->json(new BaseResponse(404, "Family not found!"), 404);
        }

        try {
            $familyMember = FamilyMember::where('family', $id);
            $count = $familyMember->count();

            if (($count - 1) == 0) {
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

    public function getFamilyMember(Request $request, string $id): JsonResponse
    {
        $resource = $this->familyService->getMember($id); // AnonymousResourceCollection

        return response()->json(new PaginationResponse(
            status: 200,
            message: "Success get family member.",
            page: $resource->currentPage(),
            totalPage: $resource->lastPage(),
            totalData: $resource->total(),
            resource: $resource,
        ), 200);
    }

    public function getFamilyAdmin(Request $request, string $id): JsonResponse
    {
        $resource = $this->familyService->getAdmin($id); // AnonymousResourceCollection

        return response()->json(new PaginationResponse(
            status: 200,
            message: "Success get family admin.",
            page: $resource->currentPage(),
            totalPage: $resource->lastPage(),
            totalData: $resource->total(),
            resource: $resource,
        ), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update family", $validator->errors()), 400);
        }

        try {
            $this->familyService->updateFamily(familyId: $id, name: $request->name);

            return response()->json(new BaseResponse(200, "Success update family"));
        } catch (FamilyException $e) {
            Log::error("Failed to update family: " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to update family: " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to update family", $e->getMessage()), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function validateSecretKey(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'secret_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to validate family secret key", $validator->errors()), 400);
        }

        try {
            $familyKey = $this->familyService->validateSecretKey(
                familyId: $id,
                secretKey: $request->secret_key,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(
                200,
                'Success validate family secret key.',
                $familyKey
            ));
        } catch (FamilyException $e) {
            Log::error("Failed to validate family secret key: " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to validate family secret key: " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to validate family secret key", $e->getMessage()), 500);
        }
    }

    public function inviteMember(Request $request, string $id): JsonResponse
    {
        try {
            $familyInvitation = $this->familyService->inviteMember(
                familyId: $id,
                token: $request->bearerToken()
            );
            return response()->json(new BaseResponse(200, "Success invite family member", $familyInvitation), 200);
        } catch (FamilyException $e) {
            Log::error("Failed to invite family member: " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to invite family member: " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to invite family member", $e->getMessage()), 500);
        }
    }

    public function responseInvitation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invitation_id' => 'required|string',
            'family_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to response family invitation", $validator->errors()), 400);
        }

        try {
            $responseInvitation = $this->familyService->responseInvitation(
                invitationId: $request->invitation_id,
                familyId: $request->family_id,
                token: $request->bearerToken(),
            );

            return response()->json(new BaseResponse(
                200,
                'Success response family invitation.',
                $responseInvitation
            ));
        } catch (FamilyException $e) {
            Log::error("Failed to response family invitation: " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to response family invitation: " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to response family invitation", $e->getMessage()), 500);
        }
    }

    public function grantAdmin(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to grant admin family", $validator->errors()), 400);
        }

        try {
            $this->familyService->grantAdmin(
                familyId: $id,
                userId: $request->user_id,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(
                200,
                'Success grant admin family.'
            ));
        } catch (FamilyException $e) {
            Log::error("Failed to grant admin family: " . $e->getMessage());
            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error("Failed to grant admin family: " . $e->getMessage());
            return response()->json(new BaseResponse(500, "Failed to grant admin family", $e->getMessage()), 500);
        }
    }
}
