<?php

namespace Database\Factories;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * `email`/`blind_index` are derived together from a plaintext email so
     * they stay consistent (email = AES-GCM ciphertext, blind_index = HMAC
     * lookup) — use forEmail() instead of overriding `email` directly.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->emailAttributes(fake()->unique()->safeEmail()) + [
            'name' => fake()->name(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('test-auth-key'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Set a specific plaintext email, deriving matching email/blind_index.
     */
    public function forEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => $this->emailAttributes($email));
    }

    /**
     * Set a known authKey so tests can log in with it (default factory
     * password is a fixed, unknown bcrypt hash).
     */
    public function withAuthKey(string $authKey): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make($authKey),
        ]);
    }

    private function emailAttributes(string $email): array
    {
        return [
            'email' => EncryptionHelper::encryptEmail($email),
            'blind_index' => EncryptionHelper::blindIndex($email),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
