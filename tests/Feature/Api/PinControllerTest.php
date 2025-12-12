<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PinControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_pin_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/pin');

        $response->assertStatus(401);
    }

    public function test_init_pin_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/pin/init');

        $response->assertStatus(401);
    }

    public function test_delete_pin_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/auth/pin');

        $response->assertStatus(401);
    }

    public function test_verify_pin_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/pin/verify');

        $response->assertStatus(401);
    }

    public function test_forgot_pin_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/pin/forgot');

        $response->assertStatus(401);
    }

    public function test_reset_pin_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/pin/reset');

        $response->assertStatus(401);
    }
}
