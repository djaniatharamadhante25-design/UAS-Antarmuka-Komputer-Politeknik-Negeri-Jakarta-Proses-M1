<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory mengikuti kolom legacy tabel `users` Otaku Track:
 * username, email, password_hash, role.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = Str::slug(fake()->unique()->userName());

        return [
            'username' => $username,
            'email' => "{$username}@otakutrack.local",
            'password_hash' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'avatar_url' => null,
            'bio' => null,
        ];
    }

    /**
     * Menandai model sebagai admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
