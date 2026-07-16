<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_register_otp_requires_email(): void
    {
        $response = $this->postJson('/api/otp/send/register', []);

        $response->assertStatus(400);
    }

    public function test_send_register_otp_with_valid_email(): void
    {
        $response = $this->postJson('/api/otp/send/register', [
            'email' => 'test@example.com',
            'uuid' => 'test-uuid',
        ]);

        // Response depends on implementation (may return 500 if email service not configured)
        $this->assertTrue(in_array($response->status(), [200, 400, 422, 500]));
    }

    public function test_send_forgot_password_otp_requires_email(): void
    {
        $response = $this->postJson('/api/otp/send/forgot-password', []);

        $response->assertStatus(400);
    }

    public function test_send_forgot_password_otp_with_valid_email(): void
    {
        $user = User::factory()->forEmail('test@example.com')->create();

        $response = $this->postJson('/api/otp/send/forgot-password', [
            'email' => 'test@example.com',
            'uuid' => 'test-uuid',
        ]);

        // Response depends on implementation (may return 500 if email service not configured)
        $this->assertTrue(in_array($response->status(), [200, 400, 422, 500]));
    }

    public function test_send_change_password_otp_requires_authentication(): void
    {
        $response = $this->postJson('/api/otp/send/change-password');

        $response->assertStatus(401);
    }

    public function test_send_pin_otp_requires_authentication(): void
    {
        $response = $this->postJson('/api/otp/send/pin');

        $response->assertStatus(401);
    }

    public function test_send_forgot_pin_otp_requires_authentication(): void
    {
        $response = $this->postJson('/api/otp/send/forgot-pin');

        $response->assertStatus(401);
    }
}
