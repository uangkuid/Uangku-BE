<?php

namespace App\Repositories\SubCategory;

use App\Models\SubCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface SubCategoryRepository extends Repository
{
    /**
     * Create a new subcategory
     */
    public function createSubCategory(
        string $name,
        string $categoryId,
        string $userId,
        ?string $familyId = null,
    ): SubCategory;

    /**
     * Get all subcategories by category id with pagination
     */
    public function getSubCategory(string $id, string $userId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Check if a subcategory name already exists for a given category and user
     */
    public function isExistWithName(string $name, string $userId, string $categoryId, ?string $familyId = null): bool;

    /**
     * Check if a subcategory exists by id for a given user
     */
    public function isExist(string $userId, string $id): bool;
}
