<?php

namespace App\Services\General;

use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Models\SubCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LaravelEasyRepository\BaseService;

interface GeneralService extends BaseService
{
    /**
     * Get a list of all categories
     */
    public function getCategory(int $perPage = 10, ?string $transaction_type = null): AnonymousResourceCollection;

    /**
     * Get a list of all subcategories by category id
     *
     * @throws UserException
     */
    public function getSubCategory(string $id, string $token, int $perPage = 10): AnonymousResourceCollection;

    /**
     * Create a new subcategory
     *
     * @throws GeneralException
     * @throws UserException
     */
    public function createSubCategory(string $name, string $id, string $token, ?string $familyId): SubCategory;

    /**
     * Update an existing subcategory
     *
     * @throws GeneralException
     * @throws UserException
     */
    public function updateSubCategory(string $name, string $id, string $token): SubCategory;

    /**
     * Delete a subcategory
     *
     * @throws GeneralException
     * @throws UserException
     */
    public function deleteSubCategory(string $id, string $token): void;

    /**
     * Get feature status
     */
    public function getFeatureStatus(): array;

    /**
     * Get system configuration
     */
    public function getSystemConfig(): array;
}
