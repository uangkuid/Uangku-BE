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
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: '/family',
        summary: 'Create a family',
        description: 'Creates a family owned by the authenticated user. Each user can own at most one family. '
            .'public_key/wrapped_private_key are the family\'s own keypair, generated client-side.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'public_key', 'wrapped_private_key'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'public_key', type: 'string'),
                    new OA\Property(property: 'wrapped_private_key', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Family created successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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
    #[OA\Get(
        path: '/family/{id}',
        summary: 'Get family summary',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success get family', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[OA\Post(
        path: '/family/{id}/leave',
        summary: 'Leave family',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success leave family', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[OA\Get(
        path: '/family/{id}/member',
        summary: 'List family members',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success get family member', content: new OA\JsonContent(ref: '#/components/schemas/PaginationResponse')),
        ]
    )]
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

    #[OA\Get(
        path: '/family/{id}/admin',
        summary: 'List family admins',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success get family admin', content: new OA\JsonContent(ref: '#/components/schemas/PaginationResponse')),
        ]
    )]
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
    #[OA\Put(
        path: '/family/{id}',
        summary: 'Update family',
        description: 'Requires family-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [new OA\Property(property: 'name', type: 'string')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success update family', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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
    #[OA\Get(
        path: '/family/{id}/my-key',
        summary: 'Get my wrapped family key',
        description: "The current user's own wrapped family private key. Indicates a pending state if an admin "
            .'has not wrapped it for them yet (see /family/{id}/pending-keys).',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success get family key', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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
    #[OA\Get(
        path: '/family/{id}/pending-keys',
        summary: 'List members pending a wrapped family key',
        description: 'Requires family-admin role. Returns members who joined but do not have a wrapped family key '
            .'yet, with their public key so the admin can wrap the family private key for them.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success get pending family members', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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
    #[OA\Post(
        path: '/family/{id}/member-key',
        summary: 'Grant member a wrapped family key',
        description: 'Requires family-admin role. Uploads a wrapped family private key for a specific pending member.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'wrapped_private_key'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'string'),
                    new OA\Property(property: 'wrapped_private_key', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success grant family key', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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
    #[OA\Post(
        path: '/family/{id}/rotate-key',
        summary: 'Rotate family keypair',
        description: 'Requires family-admin role. Rotates the family keypair (new public key + freshly wrapped '
            .'private key for each remaining member). Call after revoking a member for full protection against '
            .'them reading newly-encrypted family data.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['public_key', 'member_keys'],
                properties: [
                    new OA\Property(property: 'public_key', type: 'string'),
                    new OA\Property(
                        property: 'member_keys',
                        type: 'array',
                        items: new OA\Items(
                            required: ['user_id', 'wrapped_private_key'],
                            properties: [
                                new OA\Property(property: 'user_id', type: 'string'),
                                new OA\Property(property: 'wrapped_private_key', type: 'string'),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success rotate family key', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[OA\Post(
        path: '/family/{id}/invite',
        summary: 'Invite a member to the family',
        description: 'Requires family-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success invite family member', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[OA\Post(
        path: '/family/join',
        summary: 'Respond to a family invitation',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['invitation_id', 'family_id'],
                properties: [
                    new OA\Property(property: 'invitation_id', type: 'string'),
                    new OA\Property(property: 'family_id', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success response family invitation', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[OA\Post(
        path: '/family/{id}/admin',
        summary: 'Grant admin role to a family member',
        description: 'Requires family-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [new OA\Property(property: 'user_id', type: 'string')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success grant admin family', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[OA\Post(
        path: '/family/{id}/member/{userId}/revoke',
        summary: 'Revoke a family member',
        description: 'Requires family-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success revoke family member', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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

    #[OA\Post(
        path: '/family/{id}/admin/{userId}/revoke',
        summary: 'Revoke admin role from a family member',
        description: 'Requires family-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Family'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success revoke family admin', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
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
