<?php

namespace App\Services\Family;

use App\Enums\RoleFamily;
use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use App\Helpers\EncryptionHelper;
use App\Repositories\FamilyKey\FamilyKeyRepository;
use App\Repositories\FamilyMember\FamilyMemberRepository;
use Illuminate\Support\Facades\Log;
use LaravelEasyRepository\Service;
use App\Repositories\Family\FamilyRepository;
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

    public function __construct(
        FamilyRepository       $mainRepository,
        FamilyMemberRepository $familyMemberRepository,
        FamilyKeyRepository    $familyKeyRepository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->familyMemberRepository = $familyMemberRepository;
        $this->familyKeyRepository = $familyKeyRepository;
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
     * @return array
     */
    function getMember(string $id): array
    {
        return $this->familyMemberRepository
            ->getFamilyMember($id)
            ->map(function ($member) {
                return [
                    'id' => $member->users->id,
                    'name' => $member->users->name,
                    'avatar' => $member->users->avatar,
                    'role' => $member->role,
                    'created_at' => $member->created_at,
                    'updated_at' => $member->updated_at,
                ];
            })
            ->toArray();
    }
}
