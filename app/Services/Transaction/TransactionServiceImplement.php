<?php

namespace App\Services\Transaction;

use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Helpers\EncryptionHelper;
use App\Models\Transaction;
use App\Repositories\FamilyKey\FamilyKeyRepository;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use App\Repositories\Transaction\TransactionRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\WalletAccess\WalletAccessRepository;
use App\Repositories\WalletSnapshot\WalletSnapshotRepository;
use LaravelEasyRepository\Service;

class TransactionServiceImplement extends Service implements TransactionService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected TransactionRepository $mainRepository;
    protected FamilyMemberRepository $familyMemberRepository;
    protected WalletAccessRepository $walletAccessRepository;
    protected SubCategoryRepository $subCategoryRepository;
    protected UserRepository $userRepository;
    protected FamilyKeyRepository $familyKeyRepository;
    protected WalletSnapshotRepository $walletSnapshotRepository;

    public function __construct(
        TransactionRepository  $mainRepository,
        FamilyMemberRepository $familyMemberRepository,
        WalletAccessRepository $walletAccessRepository,
        SubCategoryRepository  $subCategoryRepository,
        UserRepository         $userRepository,
        FamilyKeyRepository    $familyKeyRepository,
        WalletSnapshotRepository $walletSnapshotRepository,
    )
    {
        $this->mainRepository = $mainRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->walletAccessRepository = $walletAccessRepository;
        $this->subCategoryRepository = $subCategoryRepository;
        $this->userRepository = $userRepository;
        $this->familyKeyRepository = $familyKeyRepository;
        $this->walletSnapshotRepository = $walletSnapshotRepository;
    }

    /**
     * Create a new transaction.
     * @param string $userId
     * @param string $categoryId
     * @param string $walletId
     * @param string $transactionTypeId
     * @param string $amount
     * @param string $walletTransactionId
     * @param string|null $snapshotId
     * @param string|null $description
     * @param string|null $family
     * @param string|null $subCategoryId
     * @param string|null $transactionId
     * @return Transaction
     * @throws EncryptionException
     * @throws FamilyException
     * @throws GeneralException
     */
    function createTransaction(
        string $userId,
        string $categoryId,
        string $walletId,
        string $transactionTypeId,
        string $amount,
        string $walletTransactionId,
        string $snapshotId = null,
        string $description = null,
        string $family = null,
        string $subCategoryId = null,
        string $transactionId = null
    ): Transaction
    {
        if ($family != null) {
            $isExist = $this->familyMemberRepository->isHasAccess(
                userId: $userId,
                familyId: $family
            );

            if (!$isExist) {
                throw new FamilyException("You don't have access to this family");
            }
        }

        $isHasWalletAccess = $this->walletAccessRepository->isHasAccess(
            userId: $userId,
            walletId: $walletId
        );

        if (!$isHasWalletAccess) {
            throw new GeneralException("You don't have access to this wallet");
        }

        /**
         * Validate Sub Category Access
         */
        if ($subCategoryId != null) {
            $subCategory = $this->subCategoryRepository->find($subCategoryId);

            if ($subCategory == null) {
                throw new GeneralException("Sub category not found");
            }

            $isSubCategoryFamily = $subCategory->families != null;

            if ($isSubCategoryFamily) {
                $isFamilyAccess = $this->familyMemberRepository->isHasAccess(
                    userId: $userId,
                    familyId: $subCategory->families,
                );

                if (!$isFamilyAccess) {
                    throw new GeneralException("You do not have access to this family");
                }
            }

            $isSubCategoryPersonal = $subCategory->families == null;

            if ($isSubCategoryPersonal) {
                if ($subCategory->users != $userId) {
                    throw new GeneralException("You do not have access to this sub category");
                }
            }
        }

        /**
         * Get Key for Family or Personal Transaction for Encryption
         */
        if ($family != null) {
            $key = $this->familyKeyRepository->getFamilyKey($family);
        } else {
            $key = $this->userRepository->getUserKey($userId);
        }

        if ($key == null) {
            throw new GeneralException("Key not found for this family or user");
        }

        $publicKey = $key->public_key;

        if ($publicKey == null) {
            throw new GeneralException("Public key not found for this family or user");
        }

        $isHasSnapshot = $this->walletSnapshotRepository->isHasSnapshot($walletId);

        if ($snapshotId == null && $isHasSnapshot) {
            throw new GeneralException("Wallet Snapshot is required for this wallet");
        }

        return $this->mainRepository->createTransaction(
            userId: $userId,
            categoryId: $categoryId,
            walletId: $walletTransactionId,
            transactionTypeId: $transactionTypeId,
            amount: $amount,
            description: $description,
            family: $family,
            subCategoryId: $subCategoryId,
            transactionId: $transactionId
        );
    }
}
