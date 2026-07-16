<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserKey>
 */
class UserKeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'users' => User::factory(),
            'public_key' => base64_encode('fake-public-key'),
            'private_key' => base64_encode('fake-wrapped-private-key'),
            'salt' => base64_encode(random_bytes(16)),
            'hashed_pin' => null,
        ];
    }
}
