<?php

namespace App\Services\General;

use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Http\Resources\Models\CategoryResource;
use App\Http\Resources\Models\SubCategoryResource;
use App\Models\SubCategory;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\FeatureStatus\FeatureStatusRepository;
use App\Repositories\General\GeneralRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class GeneralServiceImplement extends Service implements GeneralService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected GeneralRepository $mainRepository;
    protected CategoryRepository $categoryRepository;
    protected SubCategoryRepository $subCategoryRepository;
    protected FeatureStatusRepository $featureStatusRepository;

    public function __construct(
        GeneralRepository     $mainRepository,
        CategoryRepository    $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        FeatureStatusRepository $featureStatusRepository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->categoryRepository = $categoryRepository;
        $this->subCategoryRepository = $subCategoryRepository;
        $this->featureStatusRepository = $featureStatusRepository;
    }

    /**
     * Get a list of all categories
     * @param int $perPage
     * @param string|null $transaction_type
     * @return AnonymousResourceCollection
     */
    function getCategory(int $perPage = 10, ?string $transaction_type = null): AnonymousResourceCollection
    {
        $paginator = $this->categoryRepository->getCategory($perPage, $transaction_type);

        return CategoryResource::collection($paginator);
    }

    /**
     * Get a list of all subcategories by category id
     * @param string $id
     * @param string $token
     * @param int $perPage
     * @return AnonymousResourceCollection
     * @throws UserException
     */
    function getSubCategory(string $id, string $token, int $perPage = 10): AnonymousResourceCollection
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new UserException("User not found");
        }

        $paginator = $this->subCategoryRepository->getSubCategory(
            id: $id,
            userId: $user->id,
            perPage: $perPage
        );

        return SubCategoryResource::collection($paginator);
    }

    /**
     * Create a new subcategory
     * @param string $name
     * @param string $id
     * @param string $token
     * @return SubCategory
     * @throws GeneralException
     * @throws UserException
     */
    function createSubCategory(string $name, string $id, string $token): SubCategory
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new UserException("User not found");
        }

        $category = $this->categoryRepository->find($id);

        if ($category == null) {
            throw new GeneralException("Category not found");
        }

        $isExist = $this->subCategoryRepository->isExistWithName(
            name: $name,
            userId: $user->id,
            id: $category->id
        );

        if ($isExist) {
            throw new GeneralException("Sub category {$name} already exists");
        }

        return $this->subCategoryRepository->create([
            'name' => $name,
            'categories' => $category->id,
            'users' => $user->id,
        ]);
    }

    /**
     * Update an existing subcategory
     * @param string $name
     * @param string $id
     * @param string $token
     * @return SubCategory
     * @throws GeneralException
     * @throws UserException
     */
    function updateSubCategory(string $name, string $id, string $token): SubCategory
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new UserException("User not found");
        }

        $subCategory = $this->subCategoryRepository->find($id);

        if ($subCategory == null) {
            throw new GeneralException("Sub category not found");
        }

        $this->subCategoryRepository->update($id, [
            'name' => $name,
        ]);

        return $this->subCategoryRepository->find($id) ?: throw new GeneralException("Failed to update sub category");
    }

    /**
     * Delete a subcategory
     * @param string $id
     * @param string $token
     * @return void
     * @throws GeneralException
     * @throws UserException
     */
    function deleteSubCategory(string $id, string $token): void
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new UserException("User not found");
        }

        $subCategory = $this->subCategoryRepository->find($id);

        if ($subCategory == null) {
            throw new GeneralException("Sub category not found");
        }

        $this->subCategoryRepository->delete($id);
    }

    /**
     * Get feature status
     * @return array
     */
    function getFeatureStatus(): array
    {
        $data = $this->featureStatusRepository->getFeatureStatus();
        Log::info($data);
        return $data;
    }
}
