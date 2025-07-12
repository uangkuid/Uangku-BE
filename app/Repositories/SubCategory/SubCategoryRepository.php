<?php

namespace App\Repositories\SubCategory;

use App\Models\SubCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface SubCategoryRepository extends Repository{

    /**
     * Create a new subcategory
     * @param string $name
     * @param string $categoryId
     * @param string $userId
     * @param string|null $familyId
     * @return SubCategory
     */
    function createSubCategory(
        string $name,
        string $categoryId,
        string $userId,
        ?string $familyId = null,
    ): SubCategory;

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
     * @param string $categoryId
     * @param string|null $familyId
     * @return bool
     */
    function isExistWithName(string $name, string $userId, string $categoryId, ?string $familyId = null): bool;

    /**
     * Check if a subcategory exists by id for a given user
     * @param string $userId
     * @param string $id
     * @return bool
     */
    function isExist(string $userId, string $id): bool;
}
