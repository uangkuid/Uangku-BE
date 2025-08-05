<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\Transaction\TransactionService;
use App\Services\User\UserService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{

    protected TransactionService $transactionService;
    protected UserService $userService;
    protected WalletService $walletService;

    public function __construct(
        TransactionService $transactionService,
        UserService        $userService,
        WalletService      $walletService
    )
    {
        $this->transactionService = $transactionService;
        $this->userService = $userService;
        $this->walletService = $walletService;
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|uuid',
            'wallet' => 'required|uuid',
            'transaction_type' => 'required|uuid',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create transaction", $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();
            $user = $this->userService->getUserByToken($request->bearerToken());

            // Create Wallet Transaction
            $walletTransaction = $this->walletService->createWalletTransaction(
                userId: $user->id,
                walletId: $request->wallet,
                amount: $request->amount,
                transactionTypeId: $request->transaction_type,
                family: $request->get('family_id'),
            );

            // Create Transaction
            $transaction = $this->transactionService->createTransaction(
                userId: $user->id,
                categoryId: $request->category,
                walletId: $request->wallet,
                transactionTypeId: $request->transaction_type,
                amount: $request->amount,
                walletTransactionId: $walletTransaction->id,
                snapshotId: $request->get('snapshot_id'),
                description: $request->get('description'),
                family: $request->get('family_id'),
                subCategoryId: $request->get('sub_category_id'),
                transactionId: $request->get('transaction_id')
            );

            // Create Wallet Snapshot
            $this->walletService->createWalletSnapshot(
                wallet: $request->wallet,
                walletTransaction: $walletTransaction->id,
                amount: $request->amount,
                snapshotId: $request->get('snapshot_id')
            );

            DB::commit();

            return response()->json(new BaseResponse(201, "Transaction created successfully", $transaction), 201);
        } catch (GeneralException $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(400, $e->getMessage()), 500);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(500, "Failed to create transaction", $e->getMessage()), 500);
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
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|uuid',
            'wallet' => 'required|uuid',
            'snapshot_id' => 'required|uuid',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update transaction", $validator->errors()), 400);
        }

        try {

            DB::beginTransaction();
            $user = $this->userService->getUserByToken($request->bearerToken());
            $transaction = $this->transactionService->getDetailTransaction($id);

            //TODO: Refactor Wallet Transaction Update

            // Update Wallet Transaction
            $walletTransaction = $this->walletService->updateWalletTransaction(
                transactionId: $id,
                walletId: $request->wallet,
                amount: $request->amount,
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

            DB::commit();

            return response()->json(new BaseResponse(200, "Transaction updated successfully", $transaction), 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(new BaseResponse(500, "Failed to update transaction", $e->getMessage()), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
