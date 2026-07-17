<?php

namespace Tests\Feature\Api;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\UserKey;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulates the client-side 2SKD derivation (see docs/encryption.md) so
     * tests can register/login without a real client. Delegates to the
     * canonical EncryptionHelper::deriveAuthKey() — never reimplement the
     * derivation steps here, that duplication is exactly what caused the
     * seeder to silently diverge from the real contract (faq-backend.md
     * Blocker #1). Uses 1000 iterations for speed; test vectors separately
     * cover the real 600.000-iteration count.
     */
    private function deriveAuthKey(string $password, string $secretKey, string $salt): string
    {
        return EncryptionHelper::deriveAuthKey($password, $secretKey, base64_decode($salt), 1000);
    }

    public function test_pre_register_requires_email(): void
    {
        $response = $this->postJson('/api/auth/pre-register', []);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ]);
    }

    public function test_pre_register_with_valid_email(): void
    {
        // A unique email avoids colliding with the Redis OTP session other
        // test files key under the same OtpType::Register + email — Redis
        // isn't reset by RefreshDatabase, unlike the DB.
        $response = $this->postJson('/api/auth/pre-register', [
            'email' => fake()->unique()->safeEmail(),
        ]);

        $response->assertStatus(200);
    }

    public function test_register_requires_all_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 400,
                'message' => 'Failed to create account',
            ]);
    }

    public function test_register_validates_email_format(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'otp' => '123456',
            'uuid' => 'test-uuid',
            'salt' => base64_encode(random_bytes(16)),
            'auth_key' => base64_encode(random_bytes(32)),
            'public_key' => base64_encode('fake-public-key'),
            'wrapped_private_key' => 'fake-ciphertext',
        ]);

        $response->assertStatus(400);
    }

    public function test_register_rejects_invalid_otp(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'otp' => '000000',
            'uuid' => 'test-uuid',
            'salt' => base64_encode(random_bytes(16)),
            'auth_key' => base64_encode(random_bytes(32)),
            'public_key' => base64_encode('fake-public-key'),
            'wrapped_private_key' => 'fake-ciphertext',
        ]);

        // No pre-register OTP session exists, so this must fail — not 201.
        $response->assertStatus(409);
    }

    public function test_login_requires_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(400);
    }

    public function test_login_no_longer_accepts_raw_password_or_secret_key(): void
    {
        // The old contract (password + secret_key) must be rejected: only
        // email + auth_key are valid now. This is the core assertion that
        // the server never receives the password or the secret key.
        $response = $this->postJson('/api/auth/login', [
            'email' => 'someone@example.com',
            'password' => 'whatever',
            'secret_key' => 'UANGKU-AAAAAA-BBBBBB-CCCCC-DDDDD-EEEEE',
        ]);

        $response->assertStatus(400);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'auth_key' => 'wrong-auth-key',
        ]);

        $response->assertStatus(400);
    }

    public function test_salt_returns_deterministic_decoy_for_unknown_email(): void
    {
        $first = $this->postJson('/api/auth/salt', ['email' => 'unknown@example.com']);
        $second = $this->postJson('/api/auth/salt', ['email' => 'unknown@example.com']);

        $first->assertStatus(200);
        $this->assertSame(
            $first->json('data.salt'),
            $second->json('data.salt'),
            'Repeated lookups of the same unknown email must return the same decoy salt.'
        );
    }

    public function test_salt_returns_real_salt_for_known_email(): void
    {
        $user = User::factory()->forEmail('known@example.com')->create();
        UserKey::factory()->create([
            'users' => $user->id,
            'salt' => base64_encode('a-real-16-byte-s'),
        ]);

        $response = $this->postJson('/api/auth/salt', ['email' => 'known@example.com']);

        $response->assertStatus(200)
            ->assertJsonPath('data.salt', base64_encode('a-real-16-byte-s'));
    }

    public function test_login_succeeds_with_correct_two_secret_key_derivation(): void
    {
        $password = 'CorrectHorse123!';
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';
        $salt = base64_encode(random_bytes(16));
        $authKey = $this->deriveAuthKey($password, $secretKey, $salt);

        $user = User::factory()->forEmail('login-ok@example.com')->withAuthKey($authKey)->create();
        UserKey::factory()->create(['users' => $user->id, 'salt' => $salt]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login-ok@example.com',
            'auth_key' => $authKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'refresh_token', 'public_key', 'wrapped_private_key']]);
    }

    public function test_login_fails_when_only_password_factor_is_wrong(): void
    {
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';
        $salt = base64_encode(random_bytes(16));
        $correctAuthKey = $this->deriveAuthKey('CorrectPassword!', $secretKey, $salt);
        $wrongAuthKey = $this->deriveAuthKey('WrongPassword!', $secretKey, $salt);

        $user = User::factory()->forEmail('two-factor@example.com')->withAuthKey($correctAuthKey)->create();
        UserKey::factory()->create(['users' => $user->id, 'salt' => $salt]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'two-factor@example.com',
            'auth_key' => $wrongAuthKey,
        ]);

        $response->assertStatus(400);
    }

    public function test_login_fails_when_only_secret_key_factor_is_wrong(): void
    {
        $password = 'CorrectPassword!';
        $salt = base64_encode(random_bytes(16));
        $correctAuthKey = $this->deriveAuthKey($password, 'UANGKU-REAL00-SECRET-00000-00000-00000', $salt);
        $wrongAuthKey = $this->deriveAuthKey($password, 'UANGKU-WRONG0-SECRET-00000-00000-00000', $salt);

        $user = User::factory()->forEmail('two-factor-2@example.com')->withAuthKey($correctAuthKey)->create();
        UserKey::factory()->create(['users' => $user->id, 'salt' => $salt]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'two-factor-2@example.com',
            'auth_key' => $wrongAuthKey,
        ]);

        $response->assertStatus(400);
    }

    public function test_server_never_stores_raw_password_or_secret_key(): void
    {
        $password = 'CorrectHorse123!';
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';
        $salt = base64_encode(random_bytes(16));
        $authKey = $this->deriveAuthKey($password, $secretKey, $salt);

        $user = User::factory()->forEmail('blind-check@example.com')->withAuthKey($authKey)->create();

        // The only thing persisted about credentials is a bcrypt hash of the
        // authKey — neither the raw password nor the raw secret key appear
        // anywhere in the stored row.
        $this->assertTrue(EncryptionHelper::validateSecret($authKey, $user->password));
        $this->assertStringNotContainsString($password, $user->password);
        $this->assertStringNotContainsString($secretKey, $user->password);
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(400);
    }

    public function test_forgot_password_with_valid_email(): void
    {
        // A unique email avoids colliding with the Redis OTP session other
        // test files key under the same OtpType::ForgotPassword + email —
        // Redis isn't reset by RefreshDatabase, unlike the DB.
        $email = fake()->unique()->safeEmail();
        User::factory()->forEmail($email)->create();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $email,
        ]);

        $response->assertStatus(200);
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

    /**
     * Crosses the ACTUAL register() code path (not the factory shortcut every
     * other test in this file uses) with the ACTUAL login() code path, both
     * driven by the canonical derivation function. This is the one test that
     * would have caught Blocker #1 — the seeder's hand-written derivation used
     * to silently diverge from the documented contract while every other test
     * here (which builds users via User::factory()->withAuthKey(), bypassing
     * register() entirely) stayed green regardless. See faq-backend.md.
     */
    public function test_register_then_login_round_trips_through_the_real_service(): void
    {
        $authService = app(AuthService::class);

        $password = 'CorrectHorse123!';
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';
        $rawSalt = random_bytes(16);
        $authKey = EncryptionHelper::deriveAuthKey($password, $secretKey, $rawSalt, 1000);

        $authService->register(
            name: 'Round Trip User',
            email: 'round-trip@example.com',
            authKey: $authKey,
            salt: base64_encode($rawSalt),
            publicKey: base64_encode('fake-public-key'),
            wrappedPrivateKey: 'fake-ciphertext',
            otp: '000000',
            uuid: '00000000-0000-0000-0000-000000000000',
            isSeeder: true,
            iterations: 1000,
        );

        $response = $this->postJson('/api/auth/login', [
            'email' => 'round-trip@example.com',
            'auth_key' => $authKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'refresh_token', 'public_key', 'wrapped_private_key']]);
    }

    /**
     * The decoy salt for an unregistered email must be indistinguishable from
     * a real one — not just deterministic. A real salt is random_bytes(16)
     * (arbitrary bytes). Before the Finding A fix, the decoy was built from
     * hash_hmac() without the raw-bytes flag, so it always decoded to ASCII
     * hex characters only ([0-9a-f]) — a ~2^-64 chance for a real salt to
     * look the same, i.e. a near-perfect enumeration oracle. The old test
     * here only checked determinism, which the bug still satisfied — a
     * broken property can be perfectly deterministic. See faq-backend.md
     * Finding A.
     */
    public function test_salt_decoy_is_indistinguishable_from_a_real_salt(): void
    {
        $response = $this->postJson('/api/auth/salt', ['email' => 'never-registered@example.com']);
        $response->assertStatus(200);

        $decoded = base64_decode($response->json('data.salt'), true);
        $this->assertNotFalse($decoded);
        $this->assertSame(16, strlen($decoded));

        $allHexAscii = true;
        for ($i = 0; $i < strlen($decoded); $i++) {
            $byte = ord($decoded[$i]);
            $isHexDigitByte = ($byte >= 0x30 && $byte <= 0x39) || ($byte >= 0x61 && $byte <= 0x66);
            if (! $isHexDigitByte) {
                $allHexAscii = false;
                break;
            }
        }

        $this->assertFalse(
            $allHexAscii,
            'Decoy salt decodes to bytes restricted to [0-9a-f] ASCII — distinguishable from a real random salt.'
        );
    }

    public function test_salt_returns_the_iterations_used_at_registration(): void
    {
        $user = User::factory()->forEmail('custom-iterations@example.com')->create();
        UserKey::factory()->create([
            'users' => $user->id,
            'salt' => base64_encode(random_bytes(16)),
            'iterations' => 750000,
        ]);

        $response = $this->postJson('/api/auth/salt', ['email' => 'custom-iterations@example.com']);

        $response->assertStatus(200)
            ->assertJsonPath('data.iterations', 750000);
    }

    public function test_salt_decoy_iterations_is_always_the_global_default(): void
    {
        // A per-email decoy iteration count would itself become a second
        // enumeration oracle, so the decoy path must always report the
        // global default regardless of what any real account uses.
        $response = $this->postJson('/api/auth/salt', ['email' => 'still-unregistered@example.com']);

        $response->assertStatus(200)
            ->assertJsonPath('data.iterations', EncryptionHelper::PBKDF2_ITERATIONS);
    }
}
