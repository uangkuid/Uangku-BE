<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TransactionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/transaction-type',
        summary: 'List transaction types',
        description: 'Public list of transaction types (e.g. income, expense).',
        tags: ['TransactionType'],
        responses: [
            new OA\Response(response: 200, description: 'Success Get Transaction Type', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
        ]
    )]
    public function index()
    {
        $transactionTypes = TransactionType::all();

        return response()->json(new BaseResponse(
            status: 200,
            message: 'Success Get Transaction Type',
            resource: $transactionTypes
        ));
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
        //
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
