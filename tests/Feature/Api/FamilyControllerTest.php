<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FamilyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_family_requires_authentication(): void
    {
        $response = $this->postJson('/api/family', [
            'name' => 'Test Family'
        ]);

        $response->assertStatus(401);
    }

    public function test_join_family_requires_authentication(): void
    {
        $response = $this->postJson('/api/family/join', [
            'invitation_code' => 'test-code'
        ]);

        $response->assertStatus(401);
    }

    public function test_show_family_requires_authentication(): void
    {
        $response = $this->getJson('/api/family/1');

        $response->assertStatus(401);
    }

    public function test_leave_family_requires_authentication(): void
    {
        $response = $this->postJson('/api/family/1/leave');

        $response->assertStatus(401);
    }

    public function test_get_family_member_requires_authentication(): void
    {
        $response = $this->getJson('/api/family/1/member');

        $response->assertStatus(401);
    }

    public function test_validate_secret_key_requires_authentication(): void
    {
        $response = $this->postJson('/api/family/1/validate', [
            'secret_key' => 'test-key'
        ]);

        $response->assertStatus(401);
    }

    public function test_update_family_requires_authentication(): void
    {
        $response = $this->putJson('/api/family/1', [
            'name' => 'Updated Family'
        ]);

        $response->assertStatus(401);
    }

    public function test_get_family_admin_requires_authentication(): void
    {
        $response = $this->getJson('/api/family/1/admin');

        $response->assertStatus(401);
    }

    public function test_grant_admin_requires_authentication(): void
    {
        $response = $this->postJson('/api/family/1/admin', [
            'user_id' => 1
        ]);

        $response->assertStatus(401);
    }

    public function test_revoke_admin_requires_authentication(): void
    {
        $response = $this->postJson('/api/family/1/admin/1/revoke');

        $response->assertStatus(401);
    }

    public function test_revoke_member_requires_authentication(): void
    {
        $response = $this->postJson('/api/family/1/member/1/revoke');

        $response->assertStatus(401);
    }

    public function test_invite_member_requires_authentication(): void
    {
        $response = $this->postJson('/api/family/1/invite', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(401);
    }
}
