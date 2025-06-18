<?php

namespace App\Repositories\Wallet;

use App\Enums\WalletType;
use App\Exceptions\GeneralException;
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
                    return $query->where('families', $familyId);
                })
                ->limit(1)
                ->exists();
        } else {
            return $this->model
                ->select('id')
                ->where('name', $name)
                ->whereNull('families')
                ->limit(1)
                ->exists();
        }
    }

    /**
     * Create a new wallet.
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
                'families' => $familyId,
                'type' => WalletType::Family
            ]);
        } else {
            return $this->model->create([
                'name' => $name,
                'amount' => $amount,
                'created_by' => $userId,
                'type' => WalletType::Personal
            ]);
        }
    }

    /**
     * @param string $name
     * @param string $walletId
     * @param string|null $familyId
     * @return void
     * @throws GeneralException
     */
    function updateWallet(string $name, string $walletId, ?string $familyId = null,): void
    {
        // Bangun query dasar
        $query = $this->model->where('id', $walletId);

        // Tambah syarat keluarga bila ada
        if ($familyId !== null) {
            $query->where('families', $familyId);
            $query->where('type', WalletType::Family);
        } else {
            $query->where('type', WalletType::Personal);
        }

        // Array perubahan
        $changes = ['name' => $name];
        if ($familyId !== null) {
            $changes['families'] = $familyId;
        }

        // Hanya 1 query UPDATE; cek baris terpengaruh
        $affected = $query->update($changes);

        if ($affected === 0) {
            throw new GeneralException("Failed to update wallet or wallet not found.");
        }
    }
}
