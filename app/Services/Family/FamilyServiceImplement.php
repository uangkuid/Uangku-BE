<?php

namespace App\Services\Family;

use App\Enums\RedisKey;
use App\Enums\RoleFamily;
use App\Exceptions\FamilyException;
use App\Http\Resources\Models\FamilyMemberResource;
use App\Models\FamilyMember;
use App\Repositories\Family\FamilyRepository;
use App\Repositories\FamilyKey\FamilyKeyRepository;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use App\Repositories\FamilyMemberKey\FamilyMemberKeyRepository;
use App\Repositories\Redis\RedisRepository;
use App\Repositories\S3\S3Repository;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;

class FamilyServiceImplement extends Service implements FamilyService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected FamilyRepository $mainRepository;

    protected FamilyMemberRepository $familyMemberRepository;

    protected FamilyKeyRepository $familyKeyRepository;

    protected FamilyMemberKeyRepository $familyMemberKeyRepository;

    protected S3Repository $s3Repository;

    protected UserRepository $userRepository;

    protected RedisRepository $redisRepository;

    public function __construct(
        FamilyRepository $mainRepository,
        FamilyMemberRepository $familyMemberRepository,
        FamilyKeyRepository $familyKeyRepository,
        FamilyMemberKeyRepository $familyMemberKeyRepository,
        S3Repository $s3Repository,
        UserRepository $userRepository,
        RedisRepository $redisRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->familyKeyRepository = $familyKeyRepository;
        $this->familyMemberKeyRepository = $familyMemberKeyRepository;
        $this->s3Repository = $s3Repository;
        $this->userRepository = $userRepository;
        $this->redisRepository = $redisRepository;
    }

    /**
     * Create a new Family. The client has already generated the family
     * keypair and wrapped the private key to its own public key.
     *
     * @throws FamilyException
     */
    public function createFamily(string $token, string $name, string $publicKey, string $wrappedPrivateKey): array
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        $isExist = $this->familyMemberRepository->isAlreadyFamily($user->id);
        if ($isExist) {
            throw new FamilyException('You cannot create more than one family.');
        }

        $families = $this->mainRepository->create([
            'name' => $name,
            'created_by' => $user->id,
        ]);

        $familyMember = $this->familyMemberRepository->create([
            'user' => $user->id,
            'family' => $families->id,
            'role' => RoleFamily::Owner,
        ]);

        $familyKey = $this->familyKeyRepository->create([
            'family' => $families->id,
            'public_key' => $publicKey,
        ]);

        $this->familyMemberKeyRepository->upsertMemberKey($families->id, $user->id, $wrappedPrivateKey);

        return [
            'id' => $families->id,
            'name' => $name,
            'role' => $familyMember->role,
            'public_key' => $familyKey->public_key,
            'wrapped_private_key' => $wrappedPrivateKey,
        ];
    }

    public function getMember(string $id, int $perPage = 10): AnonymousResourceCollection
    {
        $paginator = $this->familyMemberRepository->getFamilyMemberPaging($id, $perPage);

        return FamilyMemberResource::collection($paginator);
    }

    public function isHasAccess(string $id, string $token): bool
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            return false;
        }

        return $this->familyMemberRepository->isHasAccess($user->id, $id);
    }

    public function isHasAdminAccess(string $id, string $token): bool
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            return false;
        }

        return $this->familyMemberRepository->isHasAdmin($user->id, $id);
    }

    public function getAdmin(string $id, int $perPage = 10): AnonymousResourceCollection
    {
        $paginator = $this->familyMemberRepository->getFamilyAdminPaging($id, $perPage);

        return FamilyMemberResource::collection($paginator);
    }

    /**
     * Update a family's name. $name is client ciphertext (encrypted to the
     * family public key), stored as-is.
     *
     * @throws FamilyException
     */
    public function updateFamily(string $familyId, string $name): void
    {
        $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

        if ($familyKey == null) {
            throw new FamilyException('Family Key not found');
        }

        $this->mainRepository->update($familyId, ['name' => $name]);
    }

    /**
     * @throws FamilyException
     */
    public function inviteMember(string $familyId, string $token): array
    {
        $admin = JWTAuth::setToken($token)->user();

        if ($admin == null) {
            throw new FamilyException('Admin not recognized');
        }

        $familyInvitation = [
            'id' => Uuid::uuid4()->toString(),
            'family' => $familyId,
            'inviter_id' => $admin->id,
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
     * Join a family. Membership is granted immediately; the family private
     * key is not — an admin must wrap it for this member first (see
     * getPendingMembers/grantMemberKey). The client should poll getMyMemberKey.
     *
     * @throws FamilyException
     */
    public function responseInvitation(string $invitationId, string $familyId, string $token): array
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
            key: RedisKey::FamilyInvitation->value.":{$familyId}"
        );

        if ($familyInvitation == null) {
            throw new FamilyException('Family invitation not found');
        }

        $familyInvitation = json_decode($familyInvitation, true);

        if ($familyInvitation['id'] != $invitationId) {
            throw new FamilyException('Family invitation not valid');
        }

        $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

        $isHasJoinedBefore = $this->familyMemberRepository->isHasJoinedBefore($user->id, $familyId);

        if ($isHasJoinedBefore) {
            $this->familyMemberRepository->grantAccess($user->id, $familyId);
        } else {
            $this->familyMemberRepository->create([
                'user' => $user->id,
                'family' => $familyId,
                'role' => RoleFamily::Member,
            ]);
        }

        $memberKey = $this->familyMemberKeyRepository->getMemberKey($familyId, $user->id);

        return [
            'id' => $familyInvitation['id'],
            'family' => $familyInvitation['family'],
            'public_key' => $familyKey?->public_key,
            'wrapped_private_key' => $memberKey?->wrapped_private_key,
            'key_status' => $memberKey ? 'granted' : 'pending',
        ];
    }

    public function getPendingMembers(string $familyId): Collection
    {
        return $this->familyMemberKeyRepository->getPendingMembers($familyId);
    }

    /**
     * @throws FamilyException
     */
    public function grantMemberKey(string $familyId, string $userId, string $wrappedPrivateKey, string $token): void
    {
        $admin = JWTAuth::setToken($token)->user();

        if ($admin == null) {
            throw new FamilyException('Admin not recognized');
        }

        $isMember = $this->familyMemberRepository->isHasAccess($userId, $familyId);

        if (! $isMember) {
            throw new FamilyException('User is not a member of this family');
        }

        $this->familyMemberKeyRepository->upsertMemberKey($familyId, $userId, $wrappedPrivateKey);
    }

    /**
     * @throws FamilyException
     */
    public function getMyMemberKey(string $familyId, string $token): array
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

        if ($familyKey == null) {
            throw new FamilyException('Family Key not found');
        }

        $memberKey = $this->familyMemberKeyRepository->getMemberKey($familyId, $user->id);

        return [
            'public_key' => $familyKey->public_key,
            'wrapped_private_key' => $memberKey?->wrapped_private_key,
            'key_status' => $memberKey ? 'granted' : 'pending',
        ];
    }

    /**
     * Rotate the family keypair: replace the public key and re-wrap the
     * private key for each currently-active member. Any member NOT included
     * in $memberKeys (e.g. one just revoked) loses access to newly-encrypted
     * family data going forward — their previously-cached copy of the old
     * key still exists on their device, which is an inherent limitation of
     * E2EE revocation shared by every product in this space.
     *
     * @throws FamilyException
     */
    public function rotateKey(string $familyId, string $publicKey, array $memberKeys, string $token): void
    {
        $admin = JWTAuth::setToken($token)->user();

        if ($admin == null) {
            throw new FamilyException('Admin not recognized');
        }

        $familyKey = $this->familyKeyRepository->getFamilyKey($familyId);

        if ($familyKey == null) {
            throw new FamilyException('Family Key not found');
        }

        $familyKey->public_key = $publicKey;
        $familyKey->save();

        $this->familyMemberKeyRepository->deleteAllForFamily($familyId);

        foreach ($memberKeys as $memberKey) {
            $this->familyMemberKeyRepository->upsertMemberKey(
                $familyId,
                $memberKey['user_id'],
                $memberKey['wrapped_private_key']
            );
        }
    }

    public function grantAdmin(string $familyId, string $userId, string $token): void
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        $isAlreadyAccess = $this->familyMemberRepository->isHasAdmin($userId, $familyId);

        if ($isAlreadyAccess) {
            throw new FamilyException('User already has admin access');
        }

        $this->familyMemberRepository->grantAdmin($userId, $familyId);
    }

    /**
     * Revoke a member's access to the family. Their wrapped family key row is
     * deleted so they can no longer fetch it — but full protection against a
     * revoked member reading NEW family data requires the admin to also call
     * rotateKey() afterwards.
     *
     * @throws FamilyException
     */
    public function revokeMember(string $familyId, string $userId, string $token): void
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        if ($userId == $user->id) {
            throw new FamilyException('You cannot revoke your own access');
        }

        $isAlreadyAccess = $this->familyMemberRepository->isHasAccess($userId, $familyId);

        if (! $isAlreadyAccess) {
            throw new FamilyException('User has been revoked or left from this family');
        }

        $this->familyMemberRepository->revokeMember($userId, $familyId);
        $this->familyMemberKeyRepository->deleteMemberKey($familyId, $userId);
    }

    /**
     * @throws FamilyException
     */
    public function revokeAdmin(string $familyId, string $userId, string $token): void
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        if ($userId == $user->id) {
            throw new FamilyException('You cannot revoke your own access');
        }

        $isOwner = $this->familyMemberRepository->isFamilyOwner($userId, $familyId);

        if ($isOwner) {
            throw new FamilyException('User is owner of this family');
        }

        $this->familyMemberRepository->revokeAdmin($userId, $familyId);
    }

    /**
     * @throws FamilyException
     */
    public function leave(string $familyId, string $token)
    {
        $user = JWTAuth::setToken($token)->user();

        if ($user == null) {
            throw new FamilyException('User not found');
        }

        $this->familyMemberRepository->leaveFamily($user->id, $familyId);
        $this->familyMemberKeyRepository->deleteMemberKey($familyId, $user->id);
    }

    public function getFamilyUserInfo(string $userId): ?FamilyMember
    {
        return $this->familyMemberRepository->getDetailFromUser($userId);
    }

    /**
     * @throws FamilyException
     */
    public function getFamilySummary(string $familyId): array
    {
        $family = $this->mainRepository->getFamilyDetail($familyId);

        if ($family == null) {
            throw new FamilyException('Family not found');
        }

        $member = $this->familyMemberRepository->getFamilyMemberSummary($familyId);

        return [
            'id' => $family->id,
            'name' => $family->name,
            'created_by' => $family->created_by,
            'member' => $member->map(function ($member) {
                $avatar = null;

                if (! empty($member->users->avatar)) {
                    $avatar = $this->s3Repository->getData('avatar/'.$member->users->id, $member->users->avatar);
                }

                return [
                    'id' => $member->users->id,
                    'email' => $member->users->email ?? null,
                    'avatar' => $avatar,
                    'role' => $member->role,
                ];
            }),
        ];
    }
}
