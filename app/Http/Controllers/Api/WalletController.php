<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\PaginationResponse;
use App\Services\User\UserService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: '/wallet',
        summary: 'List wallets',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [
            new OA\Parameter(name: 'family_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wallet data', content: new OA\JsonContent(ref: '#/components/schemas/PaginationResponse')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getUserByToken($request->bearerToken());

        $wallet = $this->walletService->getWallet(
            userId: $user->id,
            familyId: $request->get('family_id')
        );

        return response()->json(new PaginationResponse(
            status: 200,
            message: 'Wallet data',
            page: $wallet->currentPage(),
            totalPage: $wallet->total(),
            totalData: $wallet->total(),
            resource: $wallet
        ));
    }

    /**
     * Store a newly created resource in storage. $name/$amount are ciphertext
     * the client already encrypted to the right public key.
     */
    #[OA\Post(
        path: '/wallet',
        summary: 'Create wallet',
        description: 'name/amount are ciphertext already encrypted client-side to the right public key.',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'amount'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: 'Client-encrypted wallet name'),
                    new OA\Property(property: 'amount', type: 'string', description: 'Client-encrypted initial amount'),
                    new OA\Property(property: 'family_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Wallet created successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'amount' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to create families', $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();

            $user = $this->userService->getUserByToken($request->bearerToken());

            $wallet = $this->walletService->createWallet(
                name: $request->name,
                amount: $request->amount,
                userId: $user->id,
                familyId: $request->family_id ?? null,
            );

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family created successfully.',
                [
                    'id' => $wallet['wallet']->id,
                    'name' => $request->name,
                    'amount' => $request->amount,
                ]
            ));
        } catch (FamilyException|GeneralException|UserException $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(500, 'Failed to create wallet', $e->getMessage()), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/wallet/{id}',
        summary: 'Update wallet',
        description: 'Requires wallet-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'family_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Wallet updated successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to update wallet', $validator->errors()), 400);
        }

        try {
            $this->walletService->updateWallet(
                walletId: $id,
                name: $request->name,
                familyId: $request->family_id ?? null
            );

            return response()->json(new BaseResponse(
                200,
                'Wallet updated successfully.'
            ));
        } catch (FamilyException|UserException|GeneralException $e) {
            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            return response()->json(new BaseResponse(500, 'Failed to update wallet', $e->getMessage()), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    #[OA\Post(
        path: '/wallet/{id}/status',
        summary: 'Update wallet status',
        description: 'Requires wallet-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'])]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Wallet status updated successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to update wallet status', $validator->errors()), 400);
        }

        try {
            $this->walletService->updateWalletStatus(
                walletId: $id,
                status: $request->status
            );

            return response()->json(new BaseResponse(
                200,
                'Wallet status updated successfully.'
            ));
        } catch (FamilyException|UserException|GeneralException $e) {
            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            return response()->json(new BaseResponse(500, 'Failed to update wallet status', $e->getMessage()), 500);
        }
    }

    #[OA\Get(
        path: '/wallet/{id}/member',
        summary: 'List wallet members',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Wallet member data', content: new OA\JsonContent(ref: '#/components/schemas/PaginationResponse')),
        ]
    )]
    public function getMember(Request $request, string $id)
    {
        $resource = $this->walletService->getMember(
            id: $id
        );

        return response()->json(new PaginationResponse(
            status: 200,
            message: 'Wallet member data',
            page: $resource->currentPage(),
            totalPage: $resource->lastPage(),
            totalData: $resource->total(),
            resource: $resource
        ));
    }

    #[OA\Get(
        path: '/wallet/{id}/family',
        summary: 'List family members not yet in the wallet',
        description: 'Requires wallet-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Wallet member data', content: new OA\JsonContent(ref: '#/components/schemas/PaginationResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getFamilyMember(Request $request, string $id)
    {
        try {
            $resource = $this->walletService->getFamilyNotJoinWallet(id: $id);

            return response()->json(new PaginationResponse(
                status: 200,
                message: 'Wallet member data',
                page: $resource->currentPage(),
                totalPage: $resource->lastPage(),
                totalData: $resource->total(),
                resource: $resource
            ));
        } catch (GeneralException $e) {
            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            return response()->json(new BaseResponse(500, 'Failed to get family member', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/wallet/{id}/member',
        summary: 'Add member to wallet',
        description: 'Requires wallet-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [new OA\Property(property: 'user_id', type: 'string')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Member added successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function addMember(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to add member', $validator->errors()), 400);
        }

        try {
            $this->walletService->addMember(
                id: $id,
                userId: $request->user_id
            );

            return response()->json(new BaseResponse(
                200,
                'Member added successfully.'
            ));
        } catch (GeneralException $e) {
            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            return response()->json(new BaseResponse(500, 'Failed to add member', $e->getMessage()), 500);
        }
    }

    #[OA\Post(
        path: '/wallet/{id}/member/{userId}/revoke',
        summary: 'Revoke wallet member',
        description: 'Requires wallet-admin role.',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Member revoked successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function revokeMember(Request $request, string $id, string $userId)
    {
        try {
            $this->walletService->revokeMember(
                id: $id,
                userId: $userId
            );

            return response()->json(new BaseResponse(
                200,
                'Member revoked successfully.'
            ));
        } catch (GeneralException $e) {
            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            return response()->json(new BaseResponse(500, 'Failed to revoke member', $e->getMessage()), 500);
        }
    }

    #[OA\Get(
        path: '/wallet/{id}/snapshot',
        summary: 'Get latest wallet balance snapshot',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Wallet snapshot data', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getSnapshot(Request $request, string $id)
    {
        try {
            return response()->json(new BaseResponse(
                200,
                'Wallet snapshot data',
                $this->walletService->getLatestSnapshot($id)
            ));
        } catch (Exception) {
            return response()->json(new BaseResponse(500, 'Failed to get wallet snapshot', null), 500);
        }
    }

    #[OA\Get(
        path: '/wallet/{id}/transaction',
        summary: 'Get wallet transactions (not implemented)',
        description: 'Currently always returns 404 "Not implemented". Use GET /transaction with a wallet_id filter instead.',
        security: [['bearerAuth' => []]],
        tags: ['Wallet'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 404, description: 'Not implemented', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getTransaction(Request $request, string $id)
    {
        return response()->json(new BaseResponse(404, 'Not implemented', null), 404);
        //        try {
        //            $resource = $this->walletService->getWalletTransaction($id);
        //
        //            return response()->json(new PaginationResponse(
        //                status: 200,
        //                message: "Wallet transaction data",
        //                page: $resource->currentPage(),
        //                totalPage: $resource->lastPage(),
        //                totalData: $resource->total(),
        //                resource: $resource
        //            ));
        //        } catch (Exception $e) {
        //            return response()->json(new BaseResponse(500, "Failed to get wallet transaction", $e->getMessage()), 500);
        //        }
    }
}
