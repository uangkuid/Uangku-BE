<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\PaginationResponse;
use App\Models\Category;
use App\Models\TransactionType;
use App\Services\General\GeneralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{

    protected GeneralService $generalService;

    public function __construct(GeneralService $generalService)
    {
        $this->generalService = $generalService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:categories'],
            'type' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create new categories", $validator->errors()), 400);
        }

        $transactionType = TransactionType::where('id', $request['type']);

        // Check Transaction type
        if ($transactionType->count() < 1) {
            return response()->json(new BaseResponse(400, "Transaction type not found"), 400);
        }

        $category = Category::create([
            'name' => $request['name'],
            'transaction_types' => $request['type'],
        ]);

        return response()->json(new BaseResponse(
            200,
            "Create category successful",
            $category
        ));
    }

    /**
     * Display the specified resource.
     */
    public function getCategories(Request $request)
    {
        $type = $request->query('filter');

        $resource = $this->generalService->getCategory(transaction_type: $type);

        return response()->json(new PaginationResponse(
            200,
            "Success get categories",
            page: $resource->currentPage(),
            totalPage: $resource->currentPage(),
            totalData: $resource->total(),
            resource: $resource
        ), 200);
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
        $category = Category::where('id', $id);

        if ($category->count() < 1) {
            return response()->json(new BaseResponse(400, "Category requested not found"), 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update category", $validator->errors()), 400);
        }

        $transactionType = TransactionType::where('id', $request['type']);

        // Check Transaction type
        if ($transactionType->count() < 1) {
            return response()->json(new BaseResponse(400, "Transaction type not found"), 400);
        }

        $category->update([
            'name' => $request['name'],
            'transaction_types' => $request['type'],
        ]);

        return response()->json(new BaseResponse(
            200,
            "Update category successful"
        ));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::where('id', $id);

        if ($category->count() < 1) {
            return response()->json(new BaseResponse(400, "Category requested not found"), 400);
        }

        $category->delete();

        return response()->json(new BaseResponse(
            200,
            "Delete category successful"
        ));
    }
}
