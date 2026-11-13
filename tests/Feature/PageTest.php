<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Test tidak boleh bergantung pada jaringan/API eksternal.
        Http::fake([
            '*' => Http::response(['data' => []], 200),
        ]);
    }

    public function test_home_search_dan_forum_bisa_dibuka(): void
    {
        $this->get('/')->assertOk();
        $this->get('/search?type=anime')->assertOk();
        $this->get('/search?type=manga&q=naruto')->assertOk();
        $this->get('/forum')->assertOk();
    }

    public function test_halaman_login_dan_register_bisa_dibuka(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password?token=abc')->assertOk();
    }

    public function test_halaman_404_memakai_view_kustom(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('404');
    }
}
