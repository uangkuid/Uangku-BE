<?php

namespace App\Services\Family;

use App\Exceptions\FamilyException;
use App\Models\FamilyMember;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use LaravelEasyRepository\BaseService;

/**
 * Zero-Knowledge family sharing: the family keypair is generated client-side
 * by the owner. Each member gets their own copy of the family private key,
 * wrapped (client-side) to their own public key — there is no shared
 * "family secret key" transmitted to or verified by the server.
 * See docs/encryption_refactor.md.
 */
interface FamilyService extends BaseService
{
    /**
     * @param  string  $name  Client ciphertext (encrypted to the new family public key).
     * @param  string  $publicKey  Base64 public key (plaintext by definition).
     * @param  string  $wrappedPrivateKey  Family private key wrapped to the owner's own public key.
     *
     * @throws FamilyException
     */
    public function createFamily(string $token, string $name, string $publicKey, string $wrappedPrivateKey): array;

    public function getMember(string $id, int $perPage = 10): AnonymousResourceCollection;

    public function getAdmin(string $id, int $perPage = 10): AnonymousResourceCollection;

    public function isHasAccess(string $id, string $token): bool;

    public function isHasAdminAccess(string $id, string $token): bool;

    /**
     * @throws FamilyException
     */
    public function updateFamily(string $familyId, string $name): void;

    /**
     * @throws FamilyException
     */
    public function inviteMember(string $familyId, string $token): array;

    /**
     * Join a family. The membership is created immediately, but the family
     * private key is not available until an admin wraps it for this member
     * (see getPendingMembers/grantMemberKey) — the client should poll
     * getMyMemberKey until it's ready.
     *
     * @throws FamilyException
     */
    public function responseInvitation(string $invitationId, string $familyId, string $token): array;

    /**
     * Family members awaiting a wrapped copy of the family private key, with
     * their public key so the admin's client can wrap it for them.
     */
    public function getPendingMembers(string $familyId): Collection;

    /**
     * Upload a wrapped family private key for a specific member (admin action,
     * performed by the admin's client after fetching getPendingMembers).
     *
     * @throws FamilyException
     */
    public function grantMemberKey(string $familyId, string $userId, string $wrappedPrivateKey, string $token): void;

    /**
     * Fetch the current user's own wrapped family private key.
     *
     * @throws FamilyException
     */
    public function getMyMemberKey(string $familyId, string $token): array;

    /**
     * Rotate the family keypair (new public key + freshly wrapped private key
     * for each remaining member). Use after revoking a member so their old
     * copy can no longer decrypt newly-encrypted family data.
     *
     * @param  array<int, array{user_id: string, wrapped_private_key: string}>  $memberKeys
     *
     * @throws FamilyException
     */
    public function rotateKey(string $familyId, string $publicKey, array $memberKeys, string $token): void;

    public function grantAdmin(string $familyId, string $userId, string $token): void;

    /**
     * @throws FamilyException
     */
    public function revokeMember(string $familyId, string $userId, string $token): void;

    /**
     * @throws FamilyException
     */
    public function revokeAdmin(string $familyId, string $userId, string $token): void;

    /**
     * @throws FamilyException
     */
    public function leave(string $familyId, string $token);

    public function getFamilyUserInfo(string $userId): ?FamilyMember;

    /**
     * @throws FamilyException
     */
    public function getFamilySummary(string $familyId): array;
}
