<?php

namespace App\Services\Wallet;

use App\Enums\RoleFamily;
use App\Enums\RoleWallet;
use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Helpers\EncryptionHelper;
use App\Http\Resources\Models\FamilyMemberResource;
use App\Http\Resources\Models\WalletMemberResource;
use App\Http\Resources\Models\WalletResource;
use App\Http\Resources\Models\WalletTransactionResource;
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
        WalletRepository       $mainRepository,
        WalletAccessRepository $access,
        FamilyKeyRepository    $familyKeyRepository,
        FamilyMemberRepository $familyMemberRepository,
        UserRepository         $userRepository,
        WalletTransactionRepository $walletTransactionRepository,
        WalletSnapshotRepository $walletSnapshotRepository
    )
    {
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
     * @param string $userId
     * @return array
     */
    function getWalletAccess(string $userId): array
    {
        return $this->mainRepository->getIndividualWallet($userId);
    }

    /**
     * Create a new wallet for a user.
     * @param string $name
     * @param string $userId
     * @param string|null $familyId
     * @return array
     * @throws FamilyException
     * @throws EncryptionException
     * @throws UserException
     * @throws GeneralException
     */
    function createWallet(string $name, string $userId, ?string $familyId = null): array
    {
        if ($familyId != null) {
            $isHasAdmin = $this->familyMemberRepository->isHasAdmin($userId, $familyId);

            if (!$isHasAdmin) {
                throw new FamilyException("You don't have permission to create a wallet in this family");
            }

            $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

            if ($familyKey == null) {
                throw new FamilyException("FamilyKey not found");
            }

            $name = EncryptionHelper::encryptAsymmetric(
                data: $name,
                publicKey: base64_decode($familyKey->public_key)
            );

            $amount = EncryptionHelper::encryptAsymmetric(
                data: "0",
                publicKey: base64_decode($familyKey->public_key)
            );

            $isExist = $this->mainRepository->isNameExist(name: $name, familyId: $familyId);

            if ($isExist) {
                throw new GeneralException("Wallet name already exists in this family");
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
                throw new UserException("User key not found");
            }

            $name = EncryptionHelper::encryptAsymmetric(
                data: $name,
                publicKey: base64_decode($userKey->public_key)
            );

            $amount = EncryptionHelper::encryptAsymmetric(
                data: "0",
                publicKey: base64_decode($userKey->public_key)
            );

            $isExist = $this->mainRepository->isNameExist(name: $name);

            if ($isExist) {
                throw new GeneralException("Wallet name already exists in this family");
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
            'access' => $access
        ];
    }

    /**
     * Grant access to a user for a specific wallet.
     *
     * @param string $userId
     * @param string $walletId
     * @param RoleWallet $accessType
     * @return WalletAccess
     */
    function grantAccess(string $userId, string $walletId, RoleWallet $accessType): WalletAccess
    {
        $isHasAccessBefore = $this->access->isHasAccessBefore(
            userId: $userId,
            walletId: $walletId
        );

        if ($isHasAccessBefore) {
            $this->access->grantAccess(walletId: $walletId,userId: $userId);
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

    /**
     * @param string $userId
     * @param int $perPage
     * @param string|null $familyId
     * @return AnonymousResourceCollection
     */
    function getWallet(string $userId, int $perPage = 10, ?string $familyId = null): AnonymousResourceCollection
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
     * @param string $walletId
     * @param string $userId
     * @param string|null $familyId
     * @return bool
     */
    function isHasAdminAccess(string $walletId, string $userId, ?string $familyId = null): bool
    {
        return $this->access->isHasAdminAccess(
            userId: $userId,
            walletId: $walletId
        );
    }

    /**
     * Update a wallet's data
     * @param string $walletId
     * @param string $name
     * @param string|null $familyId
     * @return void
     * @throws FamilyException
     * @throws UserException|EncryptionException
     * @throws GeneralException
     */
    function updateWallet(string $walletId, string $name, ?string $familyId = null): void
    {
        if ($familyId != null) {

            $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

            if ($familyKey == null) {
                throw new FamilyException("FamilyKey not found");
            }

            $name = EncryptionHelper::encryptAsymmetric(
                data: $name,
                publicKey: base64_decode($familyKey->public_key)
            );

            $this->mainRepository->updateWallet(
                name: $name,
                walletId: $walletId,
                familyId: $familyId
            );
        } else {
            $userKey = $this->userRepository->getUserKey(auth()->user()->id);

            if ($userKey == null) {
                throw new UserException("User key not found");
            }

            $name = EncryptionHelper::encryptAsymmetric(
                data: $name,
                publicKey: base64_decode($userKey->public_key)
            );

            $this->mainRepository->updateWallet(
                name: $name,
                walletId: $walletId,
            );
        }
    }

    /**
     * Update the status of a wallet.
     * @param string $walletId
     * @param string $status
     * @return void
     */
    function updateWalletStatus(string $walletId, string $status): void
    {
        $this->mainRepository->updateWalletStatus(
            walletId: $walletId,
            status: $status
        );
    }

    /**
     * Check if a user has access to a wallet.
     * @param string $walletId
     * @param string $userId
     * @return bool
     */
    function isHasAccess(string $walletId, string $userId): bool
    {
        return $this->access->isHasAccess(userId: $userId, walletId: $walletId);
    }

    /**
     * Get a list of users who have access to a specific wallet.
     * @param string $id
     * @param int $perPage
     * @return AnonymousResourceCollection
     */
    function getMember(string $id, int $perPage = 10): AnonymousResourceCollection
    {
        $paginator = $this->access->getAccessPaging(
            walletId: $id,
            perPage: $perPage
        );

        return WalletMemberResource::collection($paginator);
    }

    /**
     * Get a list of family members who have access to a specific wallet.
     * @param string $id
     * @param int $perPage
     * @return AnonymousResourceCollection
     * @throws GeneralException
     */
    function getFamilyNotJoinWallet(string $id, int $perPage = 10): AnonymousResourceCollection
    {
        $paginator = $this->familyMemberRepository->getMemberNotJoinWallet(
            walletId: $id,
            perPage: $perPage
        );

        return FamilyMemberResource::collection($paginator);
    }

    /**
     * Add a member to a wallet.
     * @param string $id
     * @param string $userId
     * @return array
     * @throws GeneralException
     */
    function addMember(string $id, string $userId): array
    {
        $isExist = $this->access->isHasAccess(
            userId: $userId,
            walletId: $id
        );

        if ($isExist) {
            throw new GeneralException("User already has access to this wallet");
        }

        $familyAccess = $this->familyMemberRepository->getDetailFromUser(
            userId: $userId
        );

        if ($familyAccess == null) {
            throw new GeneralException("User is not a member of any family");
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
            "access" => $access,
        ];
    }

    /**
     * Revoke a user's access to a wallet.
     * @param string $id
     * @param string $userId
     * @return void
     * @throws GeneralException
     */
    function revokeMember(string $id, string $userId): void
    {
        $isExist = $this->access->isHasAccess(
            userId: $userId,
            walletId: $id
        );

        if (!$isExist) {
            throw new GeneralException("User does not have access to this wallet");
        }

        $this->access->revokeAccess(
            walletId: $id,
            userId: $userId
        );
    }

    /**
     * Create a new wallet transaction.
     * @param string $userId
     * @param string $walletId
     * @param string $amount
     * @param string $transactionTypeId
     * @param string|null $family
     * @return WalletTransaction
     * @throws GeneralException
     * @throws FamilyException
     * @throws EncryptionException
     */
    function createWalletTransaction(
        string $userId,
        string $walletId,
        string $amount,
        string $transactionTypeId,
        ?string $family = null
    ): WalletTransaction
    {
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
            userId: $userId,
        );
    }

    /**
     * Get the latest wallet snapshot for a specific wallet.
     * @param string $walletId
     * @return WalletSnapshot|null
     */
    function getLatestSnapshot(
        string $walletId,
    ): ?WalletSnapshot
    {
        return $this->walletSnapshotRepository->getLastSnapshot($walletId);
    }

    /**
     * Create a wallet snapshot.
     * @param string $wallet
     * @param string $walletTransaction
     * @param string $amount
     * @param string|null $balance
     * @param string|null $snapshotId
     * @return WalletSnapshot
     * @throws GeneralException
     */
    function createWalletSnapshot(
        string $wallet,
        string $walletTransaction,
        string $amount,
        ?string $balance = null,
        ?string $snapshotId = null
    ): WalletSnapshot
    {
        if ($snapshotId != null) {
            $lastSnapshot = $this->walletSnapshotRepository->getLastSnapshot($wallet);

            if ($lastSnapshot != null && $lastSnapshot->id != $snapshotId) {
                throw new GeneralException("Snapshot not found or does not match the last snapshot");
            }

            if ($balance == null) {
                throw new GeneralException("Balance is required when snapshot ID is provided");
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
     * Get wallet transactions with pagination.
     * @param string $id
     * @param int $perPage
     * @return AnonymousResourceCollection
     */
    function getWalletTransaction(string $id, int $perPage = 10): AnonymousResourceCollection
    {
        $paginator = $this->walletTransactionRepository->getTransactionPaging(
            walletId: $id,
            perPage: $perPage
        );

        return WalletTransactionResource::collection($paginator);
    }

    /**
     * Update an existing wallet transaction.
     * @param string $id
     * @param string $amount
     * @param string $userId
     * @return void
     */
    function updateWalletTransaction(
        string $id,
        string $amount,
        string $userId
    ): void
    {
        $this->walletTransactionRepository->updateTransaction(
            id: $id,
            amount: $amount,userId: $userId
        );
    }

    /**
     * Get detailed information about a specific wallet transaction.
     * @param string $id
     * @return WalletTransaction|null
     */
    function getDetailWalletTransaction(string $id): ?WalletTransaction
    {
        return $this->walletTransactionRepository->getDetailWalletTransaction(id: $id);
    }

    /**
     * @param string $id
     * @param string $userId
     * @return void
     */
    function deleteWalletTransaction(string $id, string $userId): void
    {
        // TODO: Implement deleteWalletTransaction() method.
    }
}
