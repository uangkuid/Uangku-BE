<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request, string $id)
    {
        $current_user = $request->user();

        $category = Category::find($id);

        if(!$category){
            return response()->json(new BaseResponse(400, "Categories not found"), 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create new sub categories", $validator->errors()), 400);
        }

        $subCategory = SubCategory::where('name', $request->name)
            ->where('categories', $category->id)
            ->where('users', $current_user->id);

        if ($subCategory->exists()){
            return response()->json(new BaseResponse(400, "Sub categories " . $request->name . " already exist!"), 400);
        }

        $subCategory = SubCategory::create([
            'name' => $request->name,
            'categories' => $id,
            'users' => $current_user->id,
        ]);

        return response()->json(new BaseResponse(
            200,
            "Create sub category successful",
            $subCategory
        ));
    }

    /**
     * Display the specified resource.
     */
    public function getSubCategories(Request $request, string $id)
    {
        $current_user = $request->user();
        $subCategories = SubCategory::where(function ($query) use ($id, $current_user) {
            $query->where('categories', $id)->where('users', $current_user->id);
        })->orderBy('name')->paginate(10);

        $subCategories = $subCategories->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'users' => [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'avatar' => $item->user->avatar,
                ],
                'categories' => [
                    'id' => $item->category->id,
                    'name' => $item->category->name,
                    'transaction_types' => [
                        'id' => $item->category->transactionTypes->id,
                        'name' => $item->category->transactionTypes->name,
                    ],
                ],
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json(new BaseResponse(
            200,
            "Success get categories",
            $subCategories
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
    public function update(Request $request, string $categoryId, string $id)
    {
        $current_user = $request->user();

        $subCategory = SubCategory::where('id', $id)
            ->where('users', $current_user->id);

        if ($subCategory->count() < 1) {
            return response()->json(new BaseResponse(400, "Sub Category requested not found"), 400);
        }

        $category = Category::find($categoryId);

        if(!$category){
            return response()->json(new BaseResponse(400, "Categories not found"), 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create new sub categories", $validator->errors()), 400);
        }

        $subCategory->update([
            'name' => $request->name,
        ]);

        return response()->json(new BaseResponse(
            200,
            "Update sub category successful"
        ));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $categoryId, string $id)
    {
        $current_user = $request->user();

        $subCategory = SubCategory::where('id', $id)
            ->where('users', $current_user->id);

        if ($subCategory->count() < 1) {
            return response()->json(new BaseResponse(400, "Sub Category requested not found"), 400);
        }

        $category = Category::find($categoryId);

        if(!$category){
            return response()->json(new BaseResponse(400, "Categories not found"), 400);
        }

        $subCategory->delete();

        return response()->json(new BaseResponse(
            200,
            "Delete sub category successful"
        ));
    }
}
