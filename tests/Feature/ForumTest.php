<?php

namespace Tests\Feature;

use App\Models\ForumCategory;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    private function category(): ForumCategory
    {
        return ForumCategory::create([
            'name' => 'General',
            'description' => 'Diskusi umum.',
        ]);
    }

    public function test_guest_tidak_bisa_membuat_thread(): void
    {
        $this->post('/forum/thread', [
            'category_id' => $this->category()->id,
            'title' => 'Halo',
            'body' => 'Isi thread.',
        ])->assertRedirect(route('login'));
    }

    public function test_member_bisa_membuat_thread_membalas_dan_vote(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $this->actingAs($user)->post('/forum/thread', [
            'category_id' => $category->id,
            'title' => 'Anime favorit',
            'body' => 'Mari diskusi di sini.',
        ])->assertRedirect();

        $thread = ForumThread::firstOrFail();
        $this->assertDatabaseHas('forum_threads', [
            'id' => $thread->id,
            'user_id' => $user->id,
            'title' => 'Anime favorit',
        ]);

        $this->actingAs($user)->post('/forum/reply', [
            'thread_id' => $thread->id,
            'body' => 'Saya suka One Piece.',
        ])->assertRedirect(route('thread', ['id' => $thread->id]));

        $this->assertDatabaseHas('forum_replies', [
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => 'Saya suka One Piece.',
        ]);

        $this->actingAs($user)->post('/forum/vote', ['thread_id' => $thread->id]);
        $this->assertDatabaseHas('forum_votes', [
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        // Vote kedua membatalkan vote pertama (toggle).
        $this->actingAs($user)->post('/forum/vote', ['thread_id' => $thread->id]);
        $this->assertDatabaseMissing('forum_votes', [
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);
    }
}
