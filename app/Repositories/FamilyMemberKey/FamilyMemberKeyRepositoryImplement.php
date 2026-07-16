<?php

namespace App\Repositories\FamilyMemberKey;

use App\Models\FamilyMember;
use App\Models\FamilyMemberKey;
use App\Models\UserKey;
use Illuminate\Support\Collection;
use LaravelEasyRepository\Implementations\Eloquent;

class FamilyMemberKeyRepositoryImplement extends Eloquent implements FamilyMemberKeyRepository
{
    /**
     * @property Model|mixed $model;
     */
    protected FamilyMemberKey $model;

    public function __construct(FamilyMemberKey $model)
    {
        $this->model = $model;
    }

    public function getMemberKey(string $familyId, string $userId): ?FamilyMemberKey
    {
        return $this->model
            ->where('family', $familyId)
            ->where('users', $userId)
            ->first();
    }

    public function upsertMemberKey(string $familyId, string $userId, string $wrappedPrivateKey): FamilyMemberKey
    {
        return $this->model->updateOrCreate(
            ['family' => $familyId, 'users' => $userId],
            ['wrapped_private_key' => $wrappedPrivateKey]
        );
    }

    public function deleteMemberKey(string $familyId, string $userId): void
    {
        $this->model
            ->where('family', $familyId)
            ->where('users', $userId)
            ->delete();
    }

    public function deleteAllForFamily(string $familyId): void
    {
        $this->model->where('family', $familyId)->delete();
    }

    public function getPendingMembers(string $familyId): Collection
    {
        $keyedUserIds = $this->model
            ->where('family', $familyId)
            ->pluck('users');

        return FamilyMember::where('family', $familyId)
            ->whereNotIn('user', $keyedUserIds)
            ->get()
            ->map(function (FamilyMember $member) {
                $userKey = UserKey::where('users', $member->user)->first();

                return [
                    'user_id' => $member->user,
                    'public_key' => $userKey?->public_key,
                ];
            });
    }
}
