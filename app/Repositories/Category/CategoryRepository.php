<?php

namespace App\Repositories\Category;

use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface CategoryRepository extends Repository
{
    /**
     * Get all categories with pagination
     */
    public function getCategory(int $perPage = 10, ?string $transaction_type = null): LengthAwarePaginator;
}
