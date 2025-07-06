<?php

namespace App\Http\Controllers\Api;

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
            'category' => 'required|uuid:4',
            'wallet' => 'required|uuid:4',
            'transaction_type' => 'required|uuid:4',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create transaction", $validator->errors()), 400);
        }

        try {
            DB::beginTransaction();
            $user = $this->userService->getUserByToken($request->bearerToken());

            $transaction = $this->transactionService->createTransaction(
                userId: $user->id,
                categoryId: $request->category,
                walletId: $request->wallet,
                transactionTypeId: $request->transaction_type,
                amount: $request->amount,
                description: $request->get('description'),
                family: $request->get('family_id'),
                subCategoryId: $request->get('sub_category_id')
            );

            // Create Wallet Transaction
            $this->walletService->createWalletTransaction(
                userId: $user->id,
                walletId: $request->get('wallet'),
                transactionId: $transaction->id,
                amount: $request->get('amount'),
                transactionTypeId: $request->get('transaction_type')
            );

            DB::commit();

            return response()->json(new BaseResponse(201, "Transaction created successfully", $transaction), 201);
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
