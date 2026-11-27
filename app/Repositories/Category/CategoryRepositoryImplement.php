<?php

namespace App\Repositories\Category;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Implementations\Eloquent;

class CategoryRepositoryImplement extends Eloquent implements CategoryRepository
{
    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     *
     * @property Model|mixed $model;
     */
    protected Category $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Get all categories with pagination
     */
    public function getCategory(int $perPage = 10, ?string $transaction_type = null): LengthAwarePaginator
    {
        return $this->model
            ->with('transactionTypes')
            ->when($transaction_type, function ($query, $transaction_type) {
                $query->whereHas('transactionTypes', function ($q) use ($transaction_type) {
                    $q->where('name', $transaction_type);
                });
            })
            ->paginate($perPage);
    }
}
