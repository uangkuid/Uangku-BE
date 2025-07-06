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
    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getUserByToken($request->bearerToken());

        $wallet = $this->walletService->getWallet(
            userId: $user->id,
            familyId: $request->get('family_id')
        );

        return response()->json(new PaginationResponse(
            status: 200,
            message: "Wallet data",
            page: $wallet->currentPage(),
            totalPage: $wallet->total(),
            totalData: $wallet->total(),
            resource: $wallet
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

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

            DB::commit();

            return response()->json(new BaseResponse(
                201,
                'Family created successfully.',
                [
                    "id" => $wallet["wallet"]->id,
                    "name" => $request->name,
                    "amount" => "0",
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update wallet", $validator->errors()), 400);
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
            return response()->json(new BaseResponse(500, "Failed to update wallet", $e->getMessage()), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => "required|in:active,inactive",
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update wallet status", $validator->errors()), 400);
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
            return response()->json(new BaseResponse(500, "Failed to update wallet status", $e->getMessage()), 500);
        }
    }

    public function getMember(Request $request, string $id)
    {
        $resource = $this->walletService->getMember(
            id: $id
        );

        return response()->json(new PaginationResponse(
            status: 200,
            message: "Wallet member data",
            page: $resource->currentPage(),
            totalPage: $resource->lastPage(),
            totalData: $resource->total(),
            resource: $resource
        ));
    }

    public function getFamilyMember(Request $request, string $id)
    {
        try {
            $resource = $this->walletService->getFamilyNotJoinWallet(id: $id);

            return response()->json(new PaginationResponse(
                status: 200,
                message: "Wallet member data",
                page: $resource->currentPage(),
                totalPage: $resource->lastPage(),
                totalData: $resource->total(),
                resource: $resource
            ));
        } catch (GeneralException $e) {
            return response()->json(new BaseResponse(400, $e->getMessage(), null), 400);
        } catch (Exception $e) {
            return response()->json(new BaseResponse(500, "Failed to get family member", $e->getMessage()), 500);
        }
    }

    public function addMember(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to add member", $validator->errors()), 400);
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
            return response()->json(new BaseResponse(500, "Failed to add member", $e->getMessage()), 500);
        }
    }

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
            return response()->json(new BaseResponse(500, "Failed to revoke member", $e->getMessage()), 500);
        }
    }
}
