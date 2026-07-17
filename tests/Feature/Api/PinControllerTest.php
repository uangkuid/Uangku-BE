<?php

namespace Tests\Feature\Api;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\UserConfig;
use App\Models\UserKey;
use App\Repositories\Redis\RedisRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PinControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: string} [user, bearer token]
     */
    private function authenticatedUserWithPinDisabled(): array
    {
        $user = User::factory()->forEmail('pin-test-'.uniqid().'@example.com')->create();
        UserKey::factory()->create(['users' => $user->id]);
        UserConfig::create([
            'users' => $user->id,
            'is_pin_enabled' => false,
            'start_date_month' => null,
        ]);

        $token = auth('api')->login($user);

        return [$user, $token];
    }

    /**
     * Seeds the Redis PIN OTP session directly — bypassing /otp/send/pin
     * (which sends a real email) — the same shape OtpServiceImplement::
     * sendEmail() would store: {email, otp, uuid}.
     */
    private function seedPinOtpSession(string $email, string $otp, string $uuid): void
    {
        app(RedisRepository::class)->storeRedis(
            "pin:{$email}",
            json_encode(['email' => $email, 'otp' => $otp, 'uuid' => $uuid]),
            300
        );
    }

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

    /**
     * Exercises the actual create-PIN → verify-PIN round trip, which
     * previously had ZERO coverage in this file (every test here only
     * checked "requires authentication"). This is the path that runs
     * through EncryptionHelper::hashSecret()/validateSecret() for PIN — the
     * exact primitive Finding B's 72-byte bcrypt truncation bug lived in.
     * A wrong-config test suite could have this bug for years and stay
     * green, because nothing ever actually created and checked a PIN.
     */
    public function test_create_pin_then_verify_succeeds_with_correct_pin(): void
    {
        [$user, $token] = $this->authenticatedUserWithPinDisabled();
        $email = EncryptionHelper::decryptEmail($user->email);
        $this->seedPinOtpSession($email, '111111', 'uuid-create-1');

        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/pin', [
                'pin' => '123456',
                'pin_confirmation' => '123456',
                'uuid' => 'uuid-create-1',
                'otp' => '111111',
            ]);

        $createResponse->assertStatus(200);

        $verifyResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/pin/verify', ['pin' => '123456']);

        $verifyResponse->assertStatus(200);
    }

    public function test_verify_pin_rejects_a_wrong_pin(): void
    {
        [$user, $token] = $this->authenticatedUserWithPinDisabled();
        $email = EncryptionHelper::decryptEmail($user->email);
        $this->seedPinOtpSession($email, '222222', 'uuid-create-2');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/pin', [
                'pin' => '654321',
                'pin_confirmation' => '654321',
                'uuid' => 'uuid-create-2',
                'otp' => '222222',
            ])->assertStatus(200);

        $verifyResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/pin/verify', ['pin' => '000000']);

        $verifyResponse->assertStatus(400);
    }
}
