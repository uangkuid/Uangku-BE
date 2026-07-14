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
            'name' => 'required|string',
            'public_key' => 'required|string',
            'wrapped_private_key' => 'required|string',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to create families', $validator->errors()), 400);
        }

        $familyMember = FamilyMember::where('user', $current_user->id);

        if ($familyMember->count() > 0) {
            return response()->json(new BaseResponse(400, 'Users cannot create more than one family.'), 400);
        }

        try {
            DB::beginTransaction();

            $families = $this->familyService->createFamily(
                token: $request->bearerToken(),
                name: $request->name,
                publicKey: $request->public_key,
                wrappedPrivateKey: $request->wrapped_private_key,
            );

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family created successfully.',
                $families
            ));
        } catch (FamilyException $e) {
            DB::rollBack();
            Log::error('Failed to create family: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create family: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to create families', $e->getMessage()), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        try {
            $family = $this->familyService->getFamilySummary(
                familyId: $id,
            );

            return response()->json(new BaseResponse(
                200,
                'Success get family.',
                $family
            ));
        } catch (FamilyException $e) {
            Log::error('Failed to get family: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to get family: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to get family', $e->getMessage()), 500);
        }
    }

    public function leave(Request $request, string $id): JsonResponse
    {
        try {
            $this->familyService->leave($id, $request->bearerToken());

            return response()->json(new BaseResponse(200, 'Success leave family'));
        } catch (FamilyException $e) {
            Log::error('Failed to leave family: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to leave family: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to leave family', $e->getMessage()), 500);
        }
    }

    public function getFamilyMember(Request $request, string $id): JsonResponse
    {
        $resource = $this->familyService->getMember($id); // AnonymousResourceCollection

        return response()->json(new PaginationResponse(
            status: 200,
            message: 'Success get family member.',
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
            message: 'Success get family admin.',
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
            return response()->json(new BaseResponse(400, 'Failed to update family', $validator->errors()), 400);
        }

        try {
            $this->familyService->updateFamily(familyId: $id, name: $request->name);

            return response()->json(new BaseResponse(200, 'Success update family'));
        } catch (FamilyException $e) {
            Log::error('Failed to update family: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to update family: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to update family', $e->getMessage()), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * The current user's own wrapped family private key. 404-like "pending"
     * status if an admin hasn't wrapped it for them yet (see getPendingKeys).
     */
    public function myKey(Request $request, string $id): JsonResponse
    {
        try {
            $memberKey = $this->familyService->getMyMemberKey(
                familyId: $id,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(200, 'Success get family key.', $memberKey));
        } catch (FamilyException $e) {
            Log::error('Failed to get family key: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to get family key: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to get family key', $e->getMessage()), 500);
        }
    }

    /**
     * Members who joined but don't have a wrapped family key yet, with their
     * public key so the admin's client can wrap the family private key for them.
     */
    public function pendingKeys(Request $request, string $id): JsonResponse
    {
        try {
            $pending = $this->familyService->getPendingMembers($id);

            return response()->json(new BaseResponse(200, 'Success get pending family members.', $pending));
        } catch (Exception $e) {
            Log::error('Failed to get pending family members: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to get pending family members', $e->getMessage()), 500);
        }
    }

    /**
     * Admin uploads a wrapped family private key for a specific pending member.
     */
    public function grantMemberKey(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'wrapped_private_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to grant family key', $validator->errors()), 400);
        }

        try {
            $this->familyService->grantMemberKey(
                familyId: $id,
                userId: $request->user_id,
                wrappedPrivateKey: $request->wrapped_private_key,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(200, 'Success grant family key.'));
        } catch (FamilyException $e) {
            Log::error('Failed to grant family key: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to grant family key: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to grant family key', $e->getMessage()), 500);
        }
    }

    /**
     * Rotate the family keypair (new public key + freshly wrapped private key
     * for each remaining member). Call after revoking a member for full
     * protection against them reading newly-encrypted family data.
     */
    public function rotateKey(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'public_key' => 'required|string',
            'member_keys' => 'required|array|min:1',
            'member_keys.*.user_id' => 'required|string',
            'member_keys.*.wrapped_private_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to rotate family key', $validator->errors()), 400);
        }

        try {
            $this->familyService->rotateKey(
                familyId: $id,
                publicKey: $request->public_key,
                memberKeys: $request->member_keys,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(200, 'Success rotate family key.'));
        } catch (FamilyException $e) {
            Log::error('Failed to rotate family key: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to rotate family key: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to rotate family key', $e->getMessage()), 500);
        }
    }

    public function inviteMember(Request $request, string $id): JsonResponse
    {
        try {
            $familyInvitation = $this->familyService->inviteMember(
                familyId: $id,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(200, 'Success invite family member', $familyInvitation), 200);
        } catch (FamilyException $e) {
            Log::error('Failed to invite family member: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to invite family member: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to invite family member', $e->getMessage()), 500);
        }
    }

    public function responseInvitation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invitation_id' => 'required|string',
            'family_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to response family invitation', $validator->errors()), 400);
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
            Log::error('Failed to response family invitation: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to response family invitation: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to response family invitation', $e->getMessage()), 500);
        }
    }

    public function grantAdmin(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to grant admin family', $validator->errors()), 400);
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
            Log::error('Failed to grant admin family: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to grant admin family: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to grant admin family', $e->getMessage()), 500);
        }
    }

    public function revokeMember(Request $request, string $id, string $userId): JsonResponse
    {
        try {
            $this->familyService->revokeMember(
                familyId: $id,
                userId: $userId,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(
                200,
                'Success revoke family member.'
            ));
        } catch (FamilyException $e) {
            Log::error('Failed to revoke family member: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to revoke family member: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to revoke family member', $e->getMessage()), 500);
        }
    }

    public function revokeAdmin(Request $request, string $id, string $userId): JsonResponse
    {
        try {
            $this->familyService->revokeAdmin(
                familyId: $id,
                userId: $userId,
                token: $request->bearerToken()
            );

            return response()->json(new BaseResponse(
                200,
                'Success revoke family admin.'
            ));
        } catch (FamilyException $e) {
            Log::error('Failed to revoke family admin: '.$e->getMessage());

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            Log::error('Failed to revoke family admin: '.$e->getMessage());

            return response()->json(new BaseResponse(500, 'Failed to revoke family admin', $e->getMessage()), 500);
        }
    }
}
