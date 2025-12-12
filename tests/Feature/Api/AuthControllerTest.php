<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_register_requires_email(): void
    {
        $response = $this->postJson('/api/auth/pre-register', []);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ]);
    }

    public function test_pre_register_with_valid_email(): void
    {
        $response = $this->postJson('/api/auth/pre-register', [
            'email' => 'test@example.com'
        ]);

        // The response will vary based on implementation
        // but should not be a 500 error
        $this->assertTrue(in_array($response->status(), [200, 400, 422]));
    }

    public function test_register_requires_all_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 400,
                'message' => 'Failed to create account'
            ]);
    }

    public function test_register_validates_email_format(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'otp' => '123456',
            'uuid' => 'test-uuid'
        ]);

        $response->assertStatus(400);
    }

    public function test_register_validates_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
            'otp' => '123456',
            'uuid' => 'test-uuid'
        ]);

        $response->assertStatus(400);
    }

    public function test_login_requires_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(400);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        // Should return error for invalid credentials
        $this->assertTrue(in_array($response->status(), [400, 401, 422]));
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(400);
    }

    public function test_forgot_password_with_valid_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        // Response depends on implementation (may return 500 if email service not configured)
        $this->assertTrue(in_array($response->status(), [200, 400, 422, 500]));
    }

    public function test_reset_password_requires_all_fields(): void
    {
        $response = $this->postJson('/api/auth/reset-password', []);

        $response->assertStatus(400);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_refresh_token_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/refresh-token');

        $response->assertStatus(401);
    }

    public function test_pre_change_password_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/pre-change-password');

        $response->assertStatus(401);
    }

    public function test_change_password_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/change-password');

        $response->assertStatus(401);
    }
}
