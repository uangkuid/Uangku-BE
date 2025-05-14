<?php

namespace App\Services\Family;

use App\Enums\InvitationStatus;
use App\Enums\RedisKey;
use App\Enums\RoleFamily;
use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Helpers\EncryptionHelper;
use App\Repositories\Family\FamilyRepository;
use App\Repositories\FamilyInvitation\FamilyInvitationRepository;
use App\Repositories\FamilyKey\FamilyKeyRepository;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\S3\S3Repository;
use App\Repositories\User\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;
use Random\RandomException;

class FamilyServiceImplement extends Service implements FamilyService
{

    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected FamilyRepository $mainRepository;
    protected FamilyMemberRepository $familyMemberRepository;
    protected FamilyKeyRepository $familyKeyRepository;
    protected S3Repository $s3Repository;
    protected UserRepository $userRepository;
    protected RedisRepository $redisRepository;

    public function __construct(
        FamilyRepository       $mainRepository,
        FamilyMemberRepository $familyMemberRepository,
        FamilyKeyRepository    $familyKeyRepository,
        S3Repository           $s3Repository,
        UserRepository         $userRepository,
        RedisRepository        $redisRepository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->familyKeyRepository = $familyKeyRepository;
        $this->s3Repository = $s3Repository;
        $this->userRepository = $userRepository;
        $this->redisRepository = $redisRepository;
    }

    /**
     * Create a new Family
     * @param string $token
     * @param string $name
     * @return array
     * @throws RandomException
     * @throws EncryptionException
     * @throws FamilyException
     */
    function createFamily(string $token, string $name): array
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        $isExist = $this->familyMemberRepository->isAlreadyFamily($user->id);
        if ($isExist) {
            throw new FamilyException('You cannot create more than one family.');
        }

        $secretKey = EncryptionHelper::generateUsersSecretKey();
        $keyPair = EncryptionHelper::generateAsymmetricKey();

        $families = $this->mainRepository->create([
            'name' => EncryptionHelper::encryptAsymmetric($name, base64_decode($keyPair['public'])),
            'created_by' => $user->id
        ]);

        $familyMember = $this->familyMemberRepository->create([
            'user' => $user->id,
            'family' => $families->id,
            'role' => RoleFamily::Owner,
        ]);

        $familyKey = $this->familyKeyRepository->create([
            'family' => $families->id,
            'public_key' => $keyPair['public'],
            'private_key' => EncryptionHelper::encryptAsString(data: $keyPair['private'], key: $secretKey),
            'hashed_key' => EncryptionHelper::hashSecretKey($secretKey),
        ]);

        return [
            'id' => $families->id,
            'name' => $name,
            'role' => $familyMember->role,
            'secret_key' => $secretKey,
            'public_key' => $familyKey->public_key,
            'private_key' => $familyKey->private_key,
        ];
    }

    /**
     * Get a family member list
     * @param string $id
     * @param int $perPage
     * @return array
     */
    function getMember(string $id, int $perPage = 10): array
    {
        $paginator = $this->familyMemberRepository->getFamilyMember($id, $perPage);

        return $this->extractDataFamilyMember($paginator);
    }

    /**
     * Extract data family member
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function extractDataFamilyMember(LengthAwarePaginator $paginator): array
    {
        $data = $paginator->through(function ($member) {
            $avatar = null;

            if (!empty($member->users->avatar)) {
                $avatar = $this->s3Repository->getData("avatar/{$member->users->id}", $member->users->avatar);
            }

            return [
                'id' => $member->users->id,
                'email' => $member->users->email,
                'avatar' => $avatar,
                'role' => $member->role,
                'created_at' => $member->created_at,
                'updated_at' => $member->updated_at,
            ];
        });

        return [
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
            'data' => $data->items() ?? [],
        ];
    }

    /**
     * Check if a user has access to a family
     * @param string $id
     * @param string $token
     * @return bool
     */
    function isHasAccess(string $id, string $token): bool
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            return false;
        }

        return $this->familyMemberRepository->isHasAccess($user->id, $id);
    }

    /**
     * Check if a user has admin access to a family
     * @param string $id
     * @param string $token
     * @return bool
     */
    function isHasAdminAccess(string $id, string $token): bool
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            return false;
        }

        return $this->familyMemberRepository->isHasAdmin($user->id, $id);
    }

    /**
     * Get a family admin list
     * @param string $id
     * @param int $perPage
     * @return array
     */
    function getAdmin(string $id, int $perPage = 10): array
    {
        $paginator = $this->familyMemberRepository->getFamilyAdmin($id, $perPage);

        return $this->extractDataFamilyMember($paginator);
    }

    /**
     * Validate a secret key
     * @param string $familyId
     * @param string $secretKey
     * @param string $token
     * @return array
     * @throws FamilyException
     */
    function validateSecretKey(string $familyId, string $secretKey, string $token): array
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

        if ($familyKey == null) {
            throw new FamilyException('Family Key not found');
        }

        if (!EncryptionHelper::validateSecretKey($secretKey, $familyKey->hashed_key)) {
            throw new FamilyException('Invalid secret key');
        }

        return [
            'public_key' => $familyKey->public_key,
            'private_key' => $familyKey->private_key,
        ];
    }

    /**
     * Update a family data
     * @param string $familyId
     * @param string $name
     * @return void
     * @throws FamilyException
     * @throws EncryptionException
     */
    function updateFamily(string $familyId, string $name): void
    {
        $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

        if ($familyKey == null) {
            throw new FamilyException('Family Key not found');
        }

        $encryptedName = EncryptionHelper::encryptAsymmetric(
            data: $name,
            publicKey: base64_decode($familyKey->public_key)
        );

        $this->mainRepository->update($familyId, ['name' => $encryptedName]);
    }

    /**
     * Invite a member of a family
     * @param string $familyId
     * @param string $token
     * @return array
     * @throws FamilyException
     */
    function inviteMember(string $familyId, string $token): array
    {
        $admin = JWTAuth::setToken($token)->user();

        if ($admin == null) {
            throw new FamilyException('Admin not recognized');
        }

        $familyInvitation = [
            "id" => Uuid::uuid4()->toString(),
            "family" => $familyId,
            "inviter_id" => $admin->id,
        ];

        $redisKey = RedisKey::FamilyInvitation;
        $expired = now()->addSeconds(5 * 60);
        $redisExpired = (5 * 60) + 10;

        $this->redisRepository->storeRedis(
            key: "{$redisKey->value}:{$familyId}",
            value: json_encode($familyInvitation),
            expire: $redisExpired // 5 minutes
        );

        return [
            'id' => $familyInvitation['id'],
            'family' => $familyInvitation['family'],
            'inviter_id' => $familyInvitation['inviter_id'],
            'expired_at_datetime' => $expired->toDateTimeString(),
            'expired_at_timestamp' => $expired->getTimestamp(),
        ];
    }

    /**
     * Response to an invitation
     * @param string $invitationId
     * @param string $familyId
     * @param string $token
     * @return array
     * @throws FamilyException
     */
    function responseInvitation(
        string $invitationId,
        string $familyId,
        string $token
    ): array
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        $isAlreadyAccess = $this->familyMemberRepository->isHasAccess($user->id, $familyId);

        if ($isAlreadyAccess) {
            throw new FamilyException('You already join this family');
        }

        $isAlreadyFamily = $this->familyMemberRepository->isAlreadyFamily($user->id);

        if ($isAlreadyFamily) {
            throw new FamilyException('You only can join one family');
        }

        $familyInvitation = $this->redisRepository->getRedis(
            key: RedisKey::FamilyInvitation->value . ":{$familyId}"
        );

        if ($familyInvitation == null) {
            throw new FamilyException('Family invitation not found');
        }

        $familyInvitation = json_decode($familyInvitation, true);
        if ($familyInvitation['id'] != $invitationId) {
            throw new FamilyException('Family invitation not valid');
        }

        $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

        $this->familyMemberRepository->create([
            'user' => $user->id,
            'family' => $familyId,
            'role' => RoleFamily::Member,
        ]);

        return [
            'id' => $familyInvitation['id'],
            'family' => $familyInvitation['family'],
            'public_key' => $familyKey['public_key'],
            'private_key' => $familyKey['private_key'],
        ];
    }
}
