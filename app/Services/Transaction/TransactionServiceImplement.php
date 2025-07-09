<?php

namespace App\Services\Transaction;

use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use App\Repositories\Transaction\TransactionRepository;
use App\Repositories\WalletAccess\WalletAccessRepository;
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

    public function __construct(
        TransactionRepository  $mainRepository,
        FamilyMemberRepository $familyMemberRepository,
        WalletAccessRepository $walletAccessRepository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->walletAccessRepository = $walletAccessRepository;
    }

    /**
     * Create a new transaction.
     * @param string $userId
     * @param string $categoryId
     * @param string $walletId
     * @param string $transactionTypeId
     * @param string $amount
     * @param string|null $description
     * @param string|null $family
     * @param string|null $subCategoryId
     * @return array
     * @throws FamilyException
     * @throws GeneralException
     */
    function createTransaction(
        string $userId,
        string $categoryId,
        string $walletId,
        string $transactionTypeId,
        string $amount,
        string $description = null,
        string $family = null,
        string $subCategoryId = null
    ): array
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


        return [];
    }
}
