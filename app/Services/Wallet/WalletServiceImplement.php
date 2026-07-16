<?php

namespace App\Services\Wallet;

use App\Enums\RoleFamily;
use App\Enums\RoleWallet;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Http\Resources\Models\FamilyMemberResource;
use App\Http\Resources\Models\WalletMemberResource;
use App\Http\Resources\Models\WalletResource;
use App\Models\WalletAccess;
use App\Models\WalletSnapshot;
use App\Models\WalletTransaction;
use App\Repositories\FamilyKey\FamilyKeyRepository;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Wallet\WalletRepository;
use App\Repositories\WalletAccess\WalletAccessRepository;
use App\Repositories\WalletSnapshot\WalletSnapshotRepository;
use App\Repositories\WalletTransaction\WalletTransactionRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LaravelEasyRepository\Service;

class WalletServiceImplement extends Service implements WalletService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected WalletRepository $mainRepository;

    protected WalletAccessRepository $access;

    protected FamilyKeyRepository $familyKeyRepository;

    protected FamilyMemberRepository $familyMemberRepository;

    protected UserRepository $userRepository;

    protected WalletTransactionRepository $walletTransactionRepository;

    protected WalletSnapshotRepository $walletSnapshotRepository;

    public function __construct(
        WalletRepository $mainRepository,
        WalletAccessRepository $access,
        FamilyKeyRepository $familyKeyRepository,
        FamilyMemberRepository $familyMemberRepository,
        UserRepository $userRepository,
        WalletTransactionRepository $walletTransactionRepository,
        WalletSnapshotRepository $walletSnapshotRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->access = $access;
        $this->familyKeyRepository = $familyKeyRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->userRepository = $userRepository;
        $this->walletTransactionRepository = $walletTransactionRepository;
        $this->walletSnapshotRepository = $walletSnapshotRepository;
    }

    /**
     * Get wallet access for a user.
     */
    public function getWalletAccess(string $userId): array
    {
        return $this->mainRepository->getIndividualWallet($userId);
    }

    /**
     * Create a new wallet for a user. $name and $amount are ciphertext the
     * client already encrypted to the right public key (the family's if
     * $familyId is set, otherwise the user's own) — the server never sees
     * the plaintext wallet name or balance.
     *
     * Note: because $name is non-deterministic ciphertext, the "name already
     * exists" check below can no longer reliably detect duplicate plaintext
     * names — it only catches byte-identical ciphertext. This is an inherent
     * trade-off of moving encryption client-side.
     *
     * @throws FamilyException
     * @throws UserException
     * @throws GeneralException
     */
    public function createWallet(string $name, string $amount, string $userId, ?string $familyId = null): array
    {
        if ($familyId != null) {
            $isHasAdmin = $this->familyMemberRepository->isHasAdmin($userId, $familyId);

            if (! $isHasAdmin) {
                throw new FamilyException("You don't have permission to create a wallet in this family");
            }

            $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

            if ($familyKey == null) {
                throw new FamilyException('FamilyKey not found');
            }

            $isExist = $this->mainRepository->isNameExist(name: $name, familyId: $familyId);

            if ($isExist) {
                throw new GeneralException('Wallet name already exists in this family');
            }

            $wallet = $this->mainRepository->createWallet(
                name: $name,
                amount: $amount,
                userId: $userId,
                familyId: $familyId
            );
        } else {
            $userKey = $this->userRepository->getUserKey($userId);

            if ($userKey == null) {
                throw new UserException('User key not found');
            }

            $isExist = $this->mainRepository->isNameExist(name: $name);

            if ($isExist) {
                throw new GeneralException('Wallet name already exists in this family');
            }

            $wallet = $this->mainRepository->createWallet(
                name: $name,
                amount: $amount,
                userId: $userId
            );
        }

        $access = $this->grantAccess(
            userId: $userId,
            walletId: $wallet->id,
            accessType: RoleWallet::Admin
        );

        return [
            'wallet' => $wallet,
            'access' => $access,
        ];
    }

    /**
     * Grant access to a user for a specific wallet.
     */
    public function grantAccess(string $userId, string $walletId, RoleWallet $accessType): WalletAccess
    {
        $isHasAccessBefore = $this->access->isHasAccessBefore(
            userId: $userId,
            walletId: $walletId
        );

        if ($isHasAccessBefore) {
            $this->access->grantAccess(walletId: $walletId, userId: $userId);

            return $this->access->getDetailAccess(walletId: $walletId, userId: $userId);
        } else {
            return $this->access->create([
                'users' => $userId,
                'wallets' => $walletId,
                'role' => $accessType,
                'is_active' => true,
            ]);
        }
    }

    public function getWallet(string $userId, int $perPage = 10, ?string $familyId = null): AnonymousResourceCollection
    {
        $paginator = $this->access->getWalletPaging(
            userId: $userId,
            perPage: $perPage,
            familyId: $familyId
        );

        return WalletResource::collection($paginator);
    }

    /**
     * Check if a user has admin access to a wallet.
     */
    public function isHasAdminAccess(string $walletId, string $userId, ?string $familyId = null): bool
    {
        return $this->access->isHasAdminAccess(
            userId: $userId,
            walletId: $walletId
        );
    }

    /**
     * Update a wallet's data. $name is client ciphertext, stored as-is.
     *
     * @throws FamilyException
     * @throws UserException
     */
    public function updateWallet(string $walletId, string $name, ?string $familyId = null): void
    {
        if ($familyId != null) {
            $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

            if ($familyKey == null) {
                throw new FamilyException('FamilyKey not found');
            }

            $this->mainRepository->updateWallet(
                name: $name,
                walletId: $walletId,
                familyId: $familyId
            );
        } else {
            $userKey = $this->userRepository->getUserKey(auth()->user()->id);

            if ($userKey == null) {
                throw new UserException('User key not found');
            }

            $this->mainRepository->updateWallet(
                name: $name,
                walletId: $walletId,
            );
        }
    }

    /**
     * Update the status of a wallet.
     */
    public function updateWalletStatus(string $walletId, string $status): void
    {
        $this->mainRepository->updateWalletStatus(
            walletId: $walletId,
            status: $status
        );
    }

    /**
     * Check if a user has access to a wallet.
     */
    public function isHasAccess(string $walletId, string $userId): bool
    {
        return $this->access->isHasAccess(userId: $userId, walletId: $walletId);
    }

    /**
     * Get a list of users who have access to a specific wallet.
     */
    public function getMember(string $id, int $perPage = 10): AnonymousResourceCollection
    {
        $paginator = $this->access->getAccessPaging(
            walletId: $id,
            perPage: $perPage
        );

        return WalletMemberResource::collection($paginator);
    }

    /**
     * Get a list of family members who have access to a specific wallet.
     *
     * @throws GeneralException
     */
    public function getFamilyNotJoinWallet(string $id, int $perPage = 10): AnonymousResourceCollection
    {
        $paginator = $this->familyMemberRepository->getMemberNotJoinWallet(
            walletId: $id,
            perPage: $perPage
        );

        return FamilyMemberResource::collection($paginator);
    }

    /**
     * Add a member to a wallet.
     *
     * @throws GeneralException
     */
    public function addMember(string $id, string $userId): array
    {
        $isExist = $this->access->isHasAccess(
            userId: $userId,
            walletId: $id
        );

        if ($isExist) {
            throw new GeneralException('User already has access to this wallet');
        }

        $familyAccess = $this->familyMemberRepository->getDetailFromUser(
            userId: $userId
        );

        if ($familyAccess == null) {
            throw new GeneralException('User is not a member of any family');
        }

        if ($familyAccess->role == RoleFamily::Member->value) {
            $access = $this->grantAccess(
                userId: $userId,
                walletId: $id,
                accessType: RoleWallet::Member
            );
        } else {
            $access = $this->grantAccess(
                userId: $userId,
                walletId: $id,
                accessType: RoleWallet::Admin
            );
        }

        return [
            'access' => $access,
        ];
    }

    /**
     * Revoke a user's access to a wallet.
     *
     * @throws GeneralException
     */
    public function revokeMember(string $id, string $userId): void
    {
        $isExist = $this->access->isHasAccess(
            userId: $userId,
            walletId: $id
        );

        if (! $isExist) {
            throw new GeneralException('User does not have access to this wallet');
        }

        $this->access->revokeAccess(
            walletId: $id,
            userId: $userId
        );
    }

    /**
     * Create a new wallet transaction.
     *
     * @throws GeneralException
     * @throws FamilyException
     */
    public function createWalletTransaction(
        string $userId,
        string $walletId,
        string $amount,
        string $transactionTypeId,
        string $transactionId,
        ?string $family = null
    ): WalletTransaction {
        $walletAccess = $this->access->getDetailAccess(
            walletId: $walletId,
            userId: $userId
        );

        if ($walletAccess == null) {
            throw new GeneralException("You don't have access to this wallet");
        }

        return $this->walletTransactionRepository->createTransaction(
            accessId: $walletAccess->id,
            walletId: $walletId,
            amount: $amount,
            transactionType: $transactionTypeId,
            transactionId: $transactionId,
            userId: $userId,
        );
    }

    /**
     * Get the latest wallet snapshot for a specific wallet.
     */
    public function getLatestSnapshot(
        string $walletId,
    ): ?WalletSnapshot {
        return $this->walletSnapshotRepository->getLastSnapshot($walletId);
    }

    /**
     * Create a wallet snapshot.
     *
     * @throws GeneralException
     */
    public function createWalletSnapshot(
        string $wallet,
        string $walletTransaction,
        string $amount,
        ?string $balance = null,
        ?string $snapshotId = null
    ): WalletSnapshot {
        if ($snapshotId != null) {
            $lastSnapshot = $this->walletSnapshotRepository->getLastSnapshot($wallet);

            if ($lastSnapshot != null && $lastSnapshot->id != $snapshotId) {
                throw new GeneralException('Snapshot not found or does not match the last snapshot');
            }

            if ($balance == null) {
                throw new GeneralException('Balance is required when snapshot ID is provided');
            }

            // amount with balance
            $amount = $balance;
        }

        return $this->walletSnapshotRepository->createWalletSnapshot(
            wallet: $wallet,
            walletTransaction: $walletTransaction,
            balance: $amount
        );
    }

    /**
     * Update an existing wallet transaction.
     */
    public function updateWalletTransaction(
        string $id,
        string $amount,
        string $userId
    ): void {
        $this->walletTransactionRepository->updateTransaction(
            id: $id,
            amount: $amount, userId: $userId
        );
    }

    /**
     * Get detailed information about a specific wallet transaction.
     */
    public function getDetailWalletTransaction(string $id): ?WalletTransaction
    {
        return $this->walletTransactionRepository->getDetailWalletTransaction(id: $id);
    }

    /**
     * Delete a wallet transaction.
     */
    public function deleteWalletTransaction(string $id, string $userId): void
    {

        $this->walletTransactionRepository->deleteTransaction(id: $id, userId: $userId);
    }

    /**
     * Get detailed wallet transaction by transaction ID.
     */
    public function getDetailWalletTransactionByTransactionId(string $transactionId): ?WalletTransaction
    {
        return $this->walletTransactionRepository->getDetailWalletTransactionByTransactionId(transactionId: $transactionId);
    }
}
