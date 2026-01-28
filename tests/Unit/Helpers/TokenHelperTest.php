<?php

namespace Tests\Unit\Helpers;

use App\Helpers\TokenHelper;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TokenHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_refresh_token_returns_string(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = TokenHelper::generateRefreshToken($user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_generate_refresh_token_creates_valid_jwt(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = TokenHelper::generateRefreshToken($user);

        // JWT tokens have 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function test_generate_refresh_token_has_extended_expiry(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = TokenHelper::generateRefreshToken($user);

        // Decode the JWT payload
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);

        $this->assertArrayHasKey('exp', $payload);

        // Check that expiry is approximately 7 days from now
        $expectedExpiry = now()->addDays(7)->timestamp;
        $this->assertGreaterThan(now()->timestamp, $payload['exp']);
        $this->assertLessThan($expectedExpiry + 60, $payload['exp']); // Allow 60 seconds tolerance
    }
}
