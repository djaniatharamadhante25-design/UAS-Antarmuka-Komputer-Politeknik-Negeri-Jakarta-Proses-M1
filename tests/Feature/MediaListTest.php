<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaListTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_bisa_menambah_mengubah_dan_menghapus_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/media-list/save', [
            'media_type' => 'anime',
            'mal_id' => 5114,
            'title' => 'Fullmetal Alchemist: Brotherhood',
            'image_url' => 'https://example.test/poster.jpg',
            'status' => 'watching',
            'score' => '9',
            'progress' => '12',
        ])->assertRedirect();

        $this->assertDatabaseHas('media_lists', [
            'user_id' => $user->id,
            'media_type' => 'anime',
            'external_id' => 5114,
            'status' => 'watching',
            'progress' => 12,
        ]);

        $this->actingAs($user)->post('/media-list/save', [
            'media_type' => 'anime',
            'mal_id' => 5114,
            'title' => 'Fullmetal Alchemist: Brotherhood',
            'status' => 'completed',
            'progress' => '64',
        ])->assertRedirect();

        $this->assertDatabaseHas('media_lists', [
            'user_id' => $user->id,
            'external_id' => 5114,
            'status' => 'completed',
            'progress' => 64,
        ]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Fullmetal Alchemist: Brotherhood');

        $this->actingAs($user)->post('/media-list/delete', [
            'media_type' => 'anime',
            'external_id' => 5114,
        ])->assertRedirect(route('profile'));

        $this->assertDatabaseMissing('media_lists', [
            'user_id' => $user->id,
            'external_id' => 5114,
        ]);
    }

    public function test_guest_tidak_bisa_menyimpan_list(): void
    {
        $this->post('/media-list/save', [
            'media_type' => 'anime',
            'mal_id' => 1,
            'title' => 'Test',
            'status' => 'watching',
        ])->assertRedirect(route('login'));
    }
}
