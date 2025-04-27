<?php

namespace App\Repositories\Wallet;

use App\Models\WalletAccess;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Wallet;

class WalletRepositoryImplement extends Eloquent implements WalletRepository
{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected Wallet $model;

    public function __construct(Wallet $model)
    {
        $this->model = $model;
    }

    /**
     * Get all individual wallets with their access.
     *
     * @param string $id
     * @return array
     */
    function getIndividualWallet(string $id): array
    {
        return $this->model
            ->whereHas('access', function ($query) use ($id) {
                $query->where('users', $id)->where('is_active', true);
            })
            ->with(['access' => function ($query) use ($id) {
                $query->where('users', $id);
            }])
            ->get()
            ->map(function ($wallet) {
                $access = $wallet->access->first();
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'amount' => $wallet->amount,
                    'role' => $access->role,
                    'is_active' => $access->is_active,
                ];
            })
            ->toArray();
    }
}
