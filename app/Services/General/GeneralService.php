<?php

namespace App\Services\General;

use App\Exceptions\UserException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LaravelEasyRepository\BaseService;

interface GeneralService extends BaseService
{

    /**
     * Get a list of all categories
     * @param int $perPage
     * @param string|null $transaction_type
     * @return AnonymousResourceCollection
     */
    function getCategory(int $perPage = 10, ?string $transaction_type = null): AnonymousResourceCollection;

    /**
     * Get a list of all subcategories by category id
     * @param string $id
     * @param string $token
     * @param int $perPage
     * @return AnonymousResourceCollection
     * @throws UserException
     */
    function getSubCategory(string $id, string $token, int $perPage = 10): AnonymousResourceCollection;
}
