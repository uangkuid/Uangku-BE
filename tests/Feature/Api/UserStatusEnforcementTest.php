<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_is_blocked_from_authenticated_api(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $response->assertStatus(403);
    }

    public function test_banned_user_is_blocked_from_authenticated_api(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Banned]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $response->assertStatus(403);
    }

    public function test_active_user_is_not_blocked_by_status_middleware(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        // Middleware status tidak boleh menolak user aktif (bukan 403).
        $this->assertNotSame(403, $response->status());
    }
}
