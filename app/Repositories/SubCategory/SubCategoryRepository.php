<?php

namespace App\Repositories\SubCategory;

use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface SubCategoryRepository extends Repository{

    /**
     * Get all subcategories by category id with pagination
     * @param string $id
     * @param string $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    function getSubCategory(string $id, string $userId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Check if a subcategory name already exists for a given category and user
     * @param string $name
     * @param string $userId
     * @param string $id
     * @return bool
     */
    function isExistWithName(string $name, string $userId, string $id): bool;

    /**
     * Check if a subcategory exists by id for a given user
     * @param string $userId
     * @param string $id
     * @return bool
     */
    function isExist(string $userId, string $id): bool;
}
