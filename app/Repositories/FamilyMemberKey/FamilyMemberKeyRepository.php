<?php

namespace App\Repositories\FamilyMemberKey;

use App\Models\FamilyMemberKey;
use Illuminate\Support\Collection;
use LaravelEasyRepository\Repository;

interface FamilyMemberKeyRepository extends Repository
{
    public function getMemberKey(string $familyId, string $userId): ?FamilyMemberKey;

    public function upsertMemberKey(string $familyId, string $userId, string $wrappedPrivateKey): FamilyMemberKey;

    public function deleteMemberKey(string $familyId, string $userId): void;

    public function deleteAllForFamily(string $familyId): void;

    /**
     * Family members who don't have a wrapped family key yet, with their
     * public key so the owner's client can wrap the family private key for them.
     * Each item: {user_id, public_key}.
     */
    public function getPendingMembers(string $familyId): Collection;
}
