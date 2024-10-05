<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\Category;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

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

        if ($type != null) {
            $isExist = TransactionType::where('name', $type)->exists();

            if (!$isExist) {
                abort(404, "Category with transaction type $type not found");
            }

            $categories = Category::whereHas('transactionTypes', function ($query) use ($type) {
                $query->where('name', $type);
            })->orderBy('name')->paginate(10);

            $categories = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'transaction_types' => [
                        'id' => $category->transactionTypes->id,
                        'name' => $category->transactionTypes->name,
                    ],
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ];
            });

            return response()->json(new BaseResponse(
                200,
                "Success get category",
                $categories
            ));
        }

        $categories = Category::has('transactionTypes')->orderBy('name')->paginate(10);

        $categories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'transaction_types' => [
                    'id' => $category->transactionTypes->id,
                    'name' => $category->transactionTypes->name,
                ],
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        });

        return response()->json(new BaseResponse(
            200,
            "Success get categories",
            $categories
        ));
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
