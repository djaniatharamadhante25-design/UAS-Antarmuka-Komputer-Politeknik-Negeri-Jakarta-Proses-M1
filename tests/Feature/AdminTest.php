<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_member_biasa_mendapat_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_bisa_membuka_semua_section(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['/admin', '/admin/users', '/admin/links', '/admin/categories', '/admin/forum'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_bisa_menambah_kategori_dan_watch_link(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/action', [
            'action' => 'admin_category_save',
            'name' => 'Seasonal',
            'description' => 'Diskusi anime musim ini.',
        ])->assertRedirect();

        $this->assertDatabaseHas('forum_categories', ['name' => 'Seasonal']);

        $this->actingAs($admin)->post('/admin/action', [
            'action' => 'admin_watch_save',
            'external_id' => 5114,
            'media_type' => 'anime',
            'provider' => 'Netflix',
            'url' => 'https://www.netflix.com/title/70204970',
        ])->assertRedirect();

        $this->assertDatabaseHas('watch_links', [
            'external_id' => 5114,
            'media_type' => 'anime',
            'provider' => 'Netflix',
            'created_by' => $admin->id,
        ]);
    }
}
