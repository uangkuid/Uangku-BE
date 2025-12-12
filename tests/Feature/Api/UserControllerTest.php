<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(401);
    }

    public function test_update_date_requires_authentication(): void
    {
        $response = $this->putJson('/api/user/date', [
            'start_date_month' => 1
        ]);

        $response->assertStatus(401);
    }

    public function test_update_avatar_requires_authentication(): void
    {
        $response = $this->postJson('/api/user/avatar');

        $response->assertStatus(401);
    }

    public function test_pre_generate_secret_key_requires_authentication(): void
    {
        $response = $this->postJson('/api/user/secret/pre-generate');

        $response->assertStatus(401);
    }

    public function test_generate_secret_key_requires_authentication(): void
    {
        $response = $this->postJson('/api/user/secret/generate');

        $response->assertStatus(401);
    }
}
