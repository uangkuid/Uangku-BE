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
}
