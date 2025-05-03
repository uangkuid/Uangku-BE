<?php

namespace App\Services\Family;

use App\Enums\RoleFamily;
use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Helpers\EncryptionHelper;
use App\Repositories\Family\FamilyRepository;
use App\Repositories\FamilyKey\FamilyKeyRepository;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use App\Repositories\S3\S3Repository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
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

    public function __construct(
        FamilyRepository       $mainRepository,
        FamilyMemberRepository $familyMemberRepository,
        FamilyKeyRepository    $familyKeyRepository,
        S3Repository           $s3Repository,
    )
    {
        $this->mainRepository = $mainRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->familyKeyRepository = $familyKeyRepository;
        $this->s3Repository = $s3Repository;
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
}
