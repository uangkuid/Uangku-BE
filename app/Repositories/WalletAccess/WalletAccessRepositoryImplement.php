<?php

namespace App\Repositories\WalletAccess;

use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\WalletAccess;

class WalletAccessRepositoryImplement extends Eloquent implements WalletAccessRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected WalletAccess $model;

    public function __construct(WalletAccess $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)

    /**
     * Get wallet access for a user.
     * @param string $userId
     * @param int $perPage
     * @param string|null $familyId
     * @return LengthAwarePaginator
     */
    function getWalletPaging(string $userId, int $perPage = 10, ?string $familyId = null): LengthAwarePaginator
    {
        if ($familyId != null) {
            return $this->model
                ->select('id', 'users', 'wallets', 'role', 'is_active', 'created_at', 'updated_at')
                ->where('users', $userId)
                ->with('wallet:id,name,amount,type,status,created_at,updated_at')
                ->paginate($perPage);
        } else {
            return $this->model
                ->select('id', 'users', 'wallets', 'role', 'is_active', 'created_at', 'updated_at')
                ->where('users', $userId)
                ->whereHas('wallet', function ($query) use ($familyId) {
                    $query->whereNull('families');
                })
                ->with('wallet:id,name,amount,type,status,created_at,updated_at')
                ->paginate($perPage);
        }
    }
}
