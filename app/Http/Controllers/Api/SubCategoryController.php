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
use OpenApi\Attributes as OA;

class SubCategoryController extends Controller
{
    protected GeneralService $generalService;

    public function __construct(
        GeneralService $generalService
    ) {
        $this->generalService = $generalService;
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/categories/{id}',
        summary: 'Create sub-category',
        security: [['bearerAuth' => []]],
        tags: ['SubCategory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Parent category ID', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'family_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Create sub category successful', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to create new sub categories', $validator->errors()), 400);
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
                'Create sub category successful',
                $resource
            ));
        } catch (UserException|GeneralException $e) {
            Log::error('Failed create sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error('Failed create sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 500,
                message: 'Failed create sub categories ',
                resource: $e->getMessage()
            ), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/categories/{id}',
        summary: 'List sub-categories of a category',
        security: [['bearerAuth' => []]],
        tags: ['SubCategory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Parent category ID', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success get sub categories', content: new OA\JsonContent(ref: '#/components/schemas/PaginationResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getSubCategories(Request $request, string $id)
    {
        try {
            $resource = $this->generalService->getSubCategory(
                id: $id,
                token: $request->bearerToken(),
            );

            return response()->json(new PaginationResponse(
                200,
                'Success get sub categories',
                page: $resource->currentPage(),
                totalPage: $resource->lastPage(),
                totalData: $resource->total(),
                resource: $resource
            ));
        } catch (UserException $e) {
            Log::error('Failed get sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error('Failed get sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 500,
                message: 'Failed get sub categories ',
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
    #[OA\Put(
        path: '/categories/{categoryId}/{id}',
        summary: 'Update sub-category',
        security: [['bearerAuth' => []]],
        tags: ['SubCategory'],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, description: 'Parent category ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Sub-category ID', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [new OA\Property(property: 'name', type: 'string', maxLength: 255)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Update sub category successful', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Validation or business error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(Request $request, string $categoryId, string $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(new BaseResponse(400, 'Failed to update sub categories', $validator->errors()), 400);
        }

        try {
            $resource = $this->generalService->updateSubCategory(
                name: $request->name,
                id: $id,
                token: $request->bearerToken(),
            );

            return response()->json(new BaseResponse(
                200,
                'Update sub category successful',
                $resource
            ));
        } catch (UserException|GeneralException $e) {
            Log::error('Failed update sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error('Failed update sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 500,
                message: 'Failed update sub categories ',
                resource: $e->getMessage()
            ), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/categories/{categoryId}/{id}',
        summary: 'Delete sub-category',
        security: [['bearerAuth' => []]],
        tags: ['SubCategory'],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, description: 'Parent category ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Sub-category ID', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Delete sub category successful', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 400, description: 'Business error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request, string $categoryId, string $id)
    {
        try {
            $this->generalService->deleteSubCategory(
                id: $id,
                token: $request->bearerToken(),
            );

            return response()->json(new BaseResponse(
                200,
                'Delete sub category successful'
            ));
        } catch (UserException|GeneralException $e) {
            Log::error('Failed delete sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 400,
                message: $e->getMessage()
            ), 400);
        } catch (Exception $e) {
            Log::error('Failed delete sub categories: '.$e->getMessage());

            return response()->json(new BaseResponse(
                status: 500,
                message: 'Failed delete sub categories ',
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
