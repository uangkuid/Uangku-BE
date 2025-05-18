<?php

namespace App\Services\General;

use App\Http\Resources\Models\CategoryResource;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\General\GeneralRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LaravelEasyRepository\Service;

class GeneralServiceImplement extends Service implements GeneralService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected GeneralRepository $mainRepository;
    protected CategoryRepository $categoryRepository;

    public function __construct(
        GeneralRepository  $mainRepository,
        CategoryRepository $categoryRepository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->categoryRepository = $categoryRepository;
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
}
