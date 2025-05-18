<?php

namespace App\Repositories\Category;

use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Repository;

interface CategoryRepository extends Repository{

    /**
     * Get all categories with pagination
     * @param int $perPage
     * @param string|null $transaction_type
     * @return LengthAwarePaginator
     */
    function getCategory(int $perPage = 10, ?string $transaction_type = null): LengthAwarePaginator;
}
