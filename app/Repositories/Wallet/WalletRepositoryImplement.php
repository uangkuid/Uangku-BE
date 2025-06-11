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

    /**
     * Check if a wallet name already exists.
     * @param string $name
     * @param string|null $familyId
     * @return bool
     */
    function isNameExist(string $name, ?string $familyId = null): bool
    {
        if ($familyId != null) {
            return $this->model
                ->select('id')
                ->where('name', $name)
                ->when($familyId, function ($query) use ($familyId) {
                    return $query->where('family', $familyId);
                })
                ->limit(1)
                ->exists();
        } else {
            return $this->model
                ->select('id')
                ->where('name', $name)
                ->whereNull('family')
                ->limit(1)
                ->exists();
        }
    }

    /**
     * C
     * @param string $name
     * @param string $amount
     * @param string $userId
     * @param string|null $familyId
     * @return Wallet
     */
    function createWallet(string $name, string $amount, string $userId, ?string $familyId = null,): Wallet
    {
        if ($familyId != null) {
            return $this->model->create([
                'name' => $name,
                'amount' => $amount,
                'created_by' => $userId,
                'family' => $familyId
            ]);
        } else {
            return $this->model->create([
                'name' => $name,
                'amount' => $amount,
                'created_by' => $userId,
            ]);
        }
    }
}
