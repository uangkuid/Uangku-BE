<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Models\SubCategory;
use Illuminate\Http\Request;

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
    public function store(Request $request)
    {
        //
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
