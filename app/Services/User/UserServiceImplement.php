<?php

namespace App\Services\User;

use App\Exceptions\UserException;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Repositories\S3\S3Repository;
use App\Repositories\User\UserRepository;
use App\Services\Family\FamilyService;
use Illuminate\Http\UploadedFile;
use LaravelEasyRepository\Service;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserServiceImplement extends Service implements UserService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected UserRepository $mainRepository;

    protected S3Repository $s3Repository;

    protected FamilyService $familyService;

    public function __construct(
        UserRepository $mainRepository,
        S3Repository $s3Repository,
        FamilyService $familyService
    ) {
        $this->mainRepository = $mainRepository;
        $this->s3Repository = $s3Repository;
        $this->familyService = $familyService;
    }

    /**
     * Get user by token
     */
    public function getUserByToken(string $token): ?User
    {
        return JWTAuth::setToken($token)->toUser();
    }

    /**
     * Update user profile. $name is a client-encrypted ciphertext (to the
     * user's own public key), stored as-is.
     *
     * @throws UserException
     */
    public function updateProfile(string $token, string $name): void
    {
        $user = $this->getUserByToken($token);

        if (! $user) {
            throw new UserException('User not found');
        }

        $user->name = $name;
        $user->save();
    }

    /**
     * Update user avatar
     *
     * @throws UserException
     */
    public function updateAvatar(string $token, UploadedFile $avatar): string
    {
        $user = $this->getUserByToken($token);

        if (! $user) {
            throw new UserException('User not found');
        }

        $filename = uniqid().'.'.$avatar->getClientOriginalExtension();

        $avatarUrl = $this->s3Repository->storeData(
            data: $avatar,
            fileName: $filename,
            path: "avatar/{$user->id}"
        );

        $user->avatar = $filename;

        $user->save();

        return $avatarUrl;
    }

    /**
     * Get user profile
     *
     * @throws UserException
     */
    public function getProfile(string $token): array
    {
        $user = $this->getUserByToken($token);
        $family = $this->familyService->getFamilyUserInfo($user->id);

        if (! $user) {
            throw new UserException('User not found');
        }

        if ($user->avatar != null && $user->avatar != '') {
            $avatar = $this->s3Repository->getData("avatar/{$user->id}", $user->avatar);
        } else {
            $avatar = null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => EncryptionHelper::decryptEmail($user->email),
            'avatar' => $avatar,
            'family' => $family,
        ];
    }
}
