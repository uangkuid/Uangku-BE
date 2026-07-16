<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\PaginationResponse;
use App\Services\Transaction\TransactionService;
use App\Services\User\UserService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    protected UserService $userService;

    protected WalletService $walletService;

    public function __construct(
        TransactionService $transactionService,
        UserService $userService,
        WalletService $walletService
    ) {
        $this->transactionService = $transactionService;
        $this->userService = $userService;
        $this->walletService = $walletService;
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/transaction',
        summary: 'List transactions',
        security: [['bearerAuth' => []]],
        tags: ['Transaction'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'family_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'transaction_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'wallet_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success get transaction', content: new OA\JsonContent(ref: '#/components/schemas/PaginationResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to get transaction', $validator->errors()), 400);
        }

        $user = $this->userService->getUserByToken(request()->bearerToken());
        $resource = $this->transactionService->getTransactionPaging(
            userId: $user->id,
            startDate: request()->get('start_date'),
            endDate: request()->get('end_date'),
            familyId: request()->get('family_id'),
            search: request()->get('search'),
            categoryId: request()->get('category_id'),
            transactionTypeId: request()->get('transaction_type_id'),
            walletId: request()->get('wallet_id'),
            perPage: request()->get('per_page', 10),
        );

        return response()->json(new PaginationResponse(
            status: 200,
            message: 'Success get transaction.',
            page: $resource->currentPage(),
            totalPage: $resource->lastPage(),
            totalData: $resource->total(),
            resource: $resource,
        ), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/transaction',
        summary: 'Create transaction',
        description: 'Creates a transaction, its wallet transaction, and a wallet balance snapshot in one call. Requires wallet membership.',
        security: [['bearerAuth' => []]],
        tags: ['Transaction'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category', 'wallet', 'transaction_type', 'amount'],
                properties: [
                    new OA\Property(property: 'category', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'wallet', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'transaction_type', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'amount', type: 'string'),
                    new OA\Property(property: 'snapshot_id', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'family_id', type: 'string', nullable: true),
                    new OA\Property(property: 'sub_category_id', type: 'string', nullable: true),
                    new OA\Property(property: 'transaction_id', type: 'string', nullable: true),
                    new OA\Property(property: 'balance', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Transaction created successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|uuid',
            'wallet' => 'required|uuid',
            'transaction_type' => 'required|uuid',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to create transaction', $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();
            $user = $this->userService->getUserByToken($request->bearerToken());

            // Create Transaction
            $transaction = $this->transactionService->createTransaction(
                userId: $user->id,
                categoryId: $request->category,
                walletId: $request->wallet,
                transactionTypeId: $request->transaction_type,
                amount: $request->amount,
                snapshotId: $request->get('snapshot_id'),
                description: $request->get('description'),
                family: $request->get('family_id'),
                subCategoryId: $request->get('sub_category_id'),
                transactionId: $request->get('transaction_id')
            );

            // Create Wallet Transaction
            $walletTransaction = $this->walletService->createWalletTransaction(
                userId: $user->id,
                walletId: $request->wallet,
                amount: $request->amount,
                transactionTypeId: $request->transaction_type,
                transactionId: $transaction->id,
                family: $request->get('family_id'),
            );

            // Create Wallet Snapshot
            $this->walletService->createWalletSnapshot(
                wallet: $request->wallet,
                walletTransaction: $walletTransaction->id,
                amount: $request->amount,
                balance: $request->get('balance'),
                snapshotId: $request->get('snapshot_id')
            );

            DB::commit();

            return response()->json(new BaseResponse(201, 'Transaction created successfully', $transaction), 201);
        } catch (GeneralException $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(400, $e->getMessage()), 500);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(500, 'Failed to create transaction', $e->getMessage()), 500);
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
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/transaction/{id}',
        summary: 'Update transaction',
        description: 'Updates the transaction, its wallet transaction, and adds a new wallet balance snapshot. Requires wallet membership.',
        security: [['bearerAuth' => []]],
        tags: ['Transaction'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category', 'wallet', 'snapshot_id', 'amount', 'balance'],
                properties: [
                    new OA\Property(property: 'category', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'wallet', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'snapshot_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'amount', type: 'string'),
                    new OA\Property(property: 'balance', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'sub_category_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Transaction updated successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|uuid',
            'wallet' => 'required|uuid',
            'snapshot_id' => 'required|uuid',
            'amount' => 'required',
            'balance' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to update transaction', $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();
            $user = $this->userService->getUserByToken($request->bearerToken());
            $walletTransaction = $this->walletService->getDetailWalletTransactionByTransactionId($id);

            if (! $walletTransaction) {
                throw new GeneralException('Wallet transaction not found');
            }

            // Update Wallet Transaction
            $this->walletService->updateWalletTransaction(
                id: $walletTransaction->id,
                amount: $request->amount,
                userId: $user->id,
            );

            // Update Transaction
            $this->transactionService->updateTransaction(
                id: $id,
                userId: $user->id,
                categoryId: $request->category,
                walletId: $request->wallet,
                amount: $request->amount,
                snapshotId: $request->snapshot_id,
                description: $request->get('description'),
                subCategoryId: $request->get('sub_category_id'),
            );

            // Add wallet snapshot
            $this->walletService->createWalletSnapshot(
                wallet: $request->wallet,
                walletTransaction: $walletTransaction->id,
                amount: $request->amount,
                balance: $request->balance,
                snapshotId: $request->snapshot_id
            );

            // Get the updated transaction details
            $transaction = $this->transactionService->getDetailTransaction($id);

            DB::commit();

            return response()->json(new BaseResponse(200, 'Transaction updated successfully', $transaction), 200);
        } catch (GeneralException $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(500, 'Failed to update transaction', $e->getMessage()), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/transaction/{id}',
        summary: 'Delete transaction',
        description: 'Deletes the transaction and its wallet transaction, then adds a new wallet balance snapshot reflecting the reversal. Requires wallet membership.',
        security: [['bearerAuth' => []]],
        tags: ['Transaction'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['wallet', 'snapshot_id', 'balance'],
                properties: [
                    new OA\Property(property: 'wallet', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'snapshot_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'balance', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Transaction deleted successfully', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'wallet' => 'required|uuid',
            'snapshot_id' => 'required|uuid',
            'balance' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to delete transaction', $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();

            $user = $this->userService->getUserByToken($request->bearerToken());
            $transaction = $this->transactionService->getDetailTransaction($id);
            $walletTransaction = $this->walletService->getDetailWalletTransactionByTransactionId($id);

            if (! $transaction) {
                throw new GeneralException('Transaction not found');
            }

            if (! $walletTransaction) {
                throw new GeneralException('Wallet transaction not found');
            }

            $this->transactionService->deleteTransaction(
                id: $id,
                walletId: $request->wallet,
                snapshotId: $request->snapshot_id
            );

            // Delete Wallet Transaction
            $this->walletService->deleteWalletTransaction(
                id: $walletTransaction->id,
                userId: $user->id
            );

            // Create Wallet Snapshot
            $this->walletService->createWalletSnapshot(
                wallet: $request->wallet,
                walletTransaction: $walletTransaction->id,
                amount: $transaction->amount,
                balance: $request->balance,
                snapshotId: $request->snapshot_id
            );

            DB::commit();

            return response()->json(new BaseResponse(200, 'Transaction deleted successfully'), 200);
        } catch (GeneralException $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(400, $e->getMessage()), 400);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(new BaseResponse(500, 'Failed to delete transaction', $e->getMessage()), 500);
        }
    }
}
