<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_update_profile_requires_authentication(): void
    {
        $response = $this->putJson('/api/user', [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_date_requires_authentication(): void
    {
        $response = $this->putJson('/api/user/date', [
            'start_date_month' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_update_avatar_requires_authentication(): void
    {
        $response = $this->postJson('/api/user/avatar');

        $response->assertStatus(401);
    }

    // Secret-key rotation was consolidated into /auth/pre-change-password and
    // /auth/change-password (see AuthControllerTest) — rotating the password
    // and rotating the secret key are the same operation server-side now.
}
