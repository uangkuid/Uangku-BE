<?php

namespace App\Services\Wallet;

use App\Enums\RoleWallet;
use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Exceptions\GeneralException;
use App\Exceptions\UserException;
use App\Helpers\EncryptionHelper;
use App\Http\Resources\Models\WalletResource;
use App\Models\WalletAccess;
use App\Repositories\FamilyKey\FamilyKeyRepository;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Wallet\WalletRepository;
use App\Repositories\WalletAccess\WalletAccessRepository;
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

    public function __construct(
        WalletRepository       $mainRepository,
        WalletAccessRepository $access,
        FamilyKeyRepository    $familyKeyRepository,
        FamilyMemberRepository $familyMemberRepository,
        UserRepository         $userRepository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->access = $access;
        $this->familyKeyRepository = $familyKeyRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->userRepository = $userRepository;
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
        return $this->access->create([
            'users' => $userId,
            'wallets' => $walletId,
            'role' => $accessType,
            'is_active' => true,
        ]);
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
}
