<?php

namespace App\Repositories\SubCategory;

use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\SubCategory;

class SubCategoryRepositoryImplement extends Eloquent implements SubCategoryRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected SubCategory $model;

    public function __construct(SubCategory $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Get all subcategories by category id with pagination
     * @param string $id
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getSubCategory(string $id, string $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->with([
                'user:id,email,avatar',
                'category:id,name,transaction_types',
                'category.transactionType:id,name',
            ])
            ->where('categories', $id)
            ->where('users', $userId)
            ->paginate($perPage);
    }

    /**
     * Check if a subcategory name already exists for a given category and user
     * @param string $name
     * @param string $userId
     * @param string $id
     * @return bool
     */
    function isExistWithName(string $name, string $userId, string $id): bool
    {
        return $this->model
            ->select('id')
            ->where('name', $name)
            ->where('users', $userId)
            ->where('categories', $id)
            ->exists();
    }

    /**
     * Check if a subcategory exists by id for a given user
     * @param string $userId
     * @param string $id
     * @return bool
     */
    function isExist(string $userId, string $id): bool
    {
        return $this->model
            ->select('id')
            ->where('users', $userId)
            ->where('id', $id)
            ->exists();
    }
}
