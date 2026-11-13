<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_bisa_register_lalu_login_dengan_username(): void
    {
        $this->post('/register', [
            'username' => 'sakura',
            'email' => 'sakura@example.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('login'));

        $user = User::where('username', 'sakura')->firstOrFail();
        $this->assertTrue(Hash::check('rahasia123', $user->password_hash));

        $this->post('/login', [
            'login' => 'sakura',
            'password' => 'rahasia123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_bisa_memakai_email(): void
    {
        $user = User::factory()->create([
            'email' => 'mail@example.test',
            'password_hash' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'login' => 'mail@example.test',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_pengguna_bisa_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_password_minimal_enam_karakter_saat_register(): void
    {
        $this->post('/register', [
            'username' => 'pendek',
            'email' => 'pendek@example.test',
            'password' => '123',
        ])->assertRedirect(route('register'));

        $this->assertDatabaseMissing('users', ['username' => 'pendek']);
    }
}
