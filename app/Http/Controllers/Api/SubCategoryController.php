<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\PaginationResponse;
use App\Models\Category;
use App\Models\SubCategory;
use App\Services\General\GeneralService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends Controller
{

    protected GeneralService $generalService;

    public function __construct(
        GeneralService $generalService
    )
    {
        $this->generalService = $generalService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to create new sub categories", $validator->errors()), 400);
        }

        try {
            $resource = $this->generalService->createSubCategory(
                name: $request->name,
                id: $id,
                token: $request->bearerToken(),
                familyId: $request->get('family_id'),
            );

            return response()->json(new BaseResponse(
                200,
                "Create sub category successful",
                $resource
            ));
        } catch (UserException|GeneralException $e) {
            Log::error("Failed create sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error("Failed create sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 500,
                message: "Failed create sub categories ",
                resource: $e->getMessage()
            ), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function getSubCategories(Request $request, string $id)
    {
        try {
            $resource = $this->generalService->getSubCategory(
                id: $id,
                token: $request->bearerToken(),
            );

            return response()->json(new PaginationResponse(
                200,
                "Success get sub categories",
                page: $resource->currentPage(),
                totalPage: $resource->lastPage(),
                totalData: $resource->total(),
                resource: $resource
            ));
        } catch (UserException $e) {
            Log::error("Failed get sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error("Failed get sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 500,
                message: "Failed get sub categories ",
                resource: $e->getMessage()
            ), 500);
        }

//        $current_user = $request->user();
//        $subCategories = SubCategory::where(function ($query) use ($id, $current_user) {
//            $query->where('categories', $id)->where('users', $current_user->id);
//        })->orderBy('name')->paginate(10);
//
//        $subCategories = $subCategories->map(function ($item) {
//            return [
//                'id' => $item->id,
//                'name' => $item->name,
//                'users' => [
//                    'id' => $item->user->id,
//                    'name' => $item->user->name,
//                    'avatar' => $item->user->avatar,
//                ],
//                'categories' => [
//                    'id' => $item->category->id,
//                    'name' => $item->category->name,
//                    'transaction_types' => [
//                        'id' => $item->category->transactionTypes->id,
//                        'name' => $item->category->transactionTypes->name,
//                    ],
//                ],
//                'created_at' => $item->created_at,
//                'updated_at' => $item->updated_at,
//            ];
//        });
//
//        return response()->json(new BaseResponse(
//            200,
//            "Success get categories",
//            $subCategories
//        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $categoryId, string $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, "Failed to update sub categories", $validator->errors()), 400);
        }

        try {
            $resource = $this->generalService->updateSubCategory(
                name: $request->name,
                id: $id,
                token: $request->bearerToken(),
            );

            return response()->json(new BaseResponse(
                200,
                "Update sub category successful",
                $resource
            ));
        } catch (UserException|GeneralException $e) {
            Log::error("Failed update sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error("Failed update sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 500,
                message: "Failed update sub categories ",
                resource: $e->getMessage()
            ), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $categoryId, string $id)
    {
        try {
            $this->generalService->deleteSubCategory(
                id: $id,
                token: $request->bearerToken(),
            );

            return response()->json(new BaseResponse(
                200,
                "Delete sub category successful"
            ));
        } catch (UserException|GeneralException $e) {
            Log::error("Failed delete sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error("Failed delete sub categories: " . $e->getMessage());
            return response()->json(new BaseResponse(
                status: 500,
                message: "Failed delete sub categories ",
                resource: $e->getMessage()
            ), 500);
        }

//        $current_user = $request->user();
//
//        $subCategory = SubCategory::where('id', $id)
//            ->where('users', $current_user->id);
//
//        if ($subCategory->count() < 1) {
//            return response()->json(new BaseResponse(400, "Sub Category requested not found"), 400);
//        }
//
//        $category = Category::find($categoryId);
//
//        if(!$category){
//            return response()->json(new BaseResponse(400, "Categories not found"), 400);
//        }
//
//        $subCategory->delete();
//
//        return response()->json(new BaseResponse(
//            200,
//            "Delete sub category successful"
//        ));
    }
}
