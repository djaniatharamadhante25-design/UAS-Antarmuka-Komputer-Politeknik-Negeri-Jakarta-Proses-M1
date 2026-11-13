<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\ForumVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ForumController extends Controller
{
    public function index(): View
    {
        $categories = ForumCategory::query()
            ->selectRaw('c.*, COUNT(t.id) AS thread_count')
            ->from('forum_categories as c')
            ->leftJoin('forum_threads as t', function ($join) {
                $join->on('t.category_id', '=', 'c.id')->where('t.is_hidden', 0);
            })
            ->groupBy('c.id')
            ->orderBy('c.id')
            ->get();

        $threads = ForumThread::query()
            ->with(['user:id,username', 'category:id,name'])
            ->selectRaw('t.*,
                (SELECT COUNT(*) FROM forum_replies r WHERE r.thread_id = t.id AND r.is_hidden = 0) AS reply_count,
                (SELECT COUNT(*) FROM forum_votes v WHERE v.thread_id = t.id) AS vote_count')
            ->from('forum_threads as t')
            ->where('t.is_hidden', 0)
            ->orderByDesc('t.updated_at')
            ->limit(30)
            ->get();

        return view('forum.forum', [
            'categories' => $categories,
            'threads' => $threads,
        ]);
    }

    public function thread(Request $request, int $id): View
    {
        $thread = ForumThread::query()
            ->with(['user:id,username,email', 'category:id,name'])
            ->where('id', $id)
            ->when(! $request->user()?->isAdmin(), function ($query) {
                $query->where('is_hidden', 0);
            })
            ->first();

        if (! $thread) {
            abort(404);
        }

        $replies = ForumReply::query()
            ->with('user:id,username')
            ->where('thread_id', $thread->id)
            ->where('is_hidden', 0)
            ->orderBy('created_at')
            ->get();

        $voteCount = ForumVote::query()->where('thread_id', $thread->id)->count();
        $myVote = $request->user()
            ? ForumVote::query()->where('thread_id', $thread->id)->where('user_id', $request->user()->id)->exists()
            : false;

        return view('forum.thread', [
            'thread' => $thread,
            'replies' => $replies,
            'voteCount' => $voteCount,
            'myVote' => $myVote,
        ]);
    }

    public function createThread(Request $request): RedirectResponse
    {
        $title = trim((string) $request->input('title'));
        $body = trim((string) $request->input('body'));
        $categoryId = (int) $request->input('category_id');

        if ($title === '' || $body === '' || ! $categoryId) {
            return redirect()->route('forum')->with('error', 'Judul, isi, dan kategori wajib diisi.');
        }

        $thread = ForumThread::create([
            'category_id' => $categoryId,
            'user_id' => $request->user()->id,
            'title' => $title,
            'body' => $body,
            'external_id' => $request->input('external_id') ? (int) $request->input('external_id') : null,
            'media_type' => in_array($request->input('media_type'), ['anime', 'manga'], true)
                ? $request->input('media_type')
                : null,
        ]);

        return redirect()->route('thread', ['id' => $thread->id]);
    }

    public function reply(Request $request): RedirectResponse
    {
        $threadId = (int) $request->input('thread_id');
        $body = trim((string) $request->input('body'));

        if ($body === '') {
            return redirect()->route('thread', ['id' => $threadId])->with('error', 'Reply tidak boleh kosong.');
        }

        $thread = ForumThread::find($threadId);
        if (! $thread || $thread->is_locked) {
            return redirect()->route('forum')->with('error', 'Thread tidak ditemukan atau sedang dikunci.');
        }

        ForumReply::create([
            'thread_id' => $threadId,
            'user_id' => $request->user()->id,
            'body' => $body,
        ]);

        return redirect()->route('thread', ['id' => $threadId]);
    }

    public function vote(Request $request): RedirectResponse
    {
        $threadId = (int) $request->input('thread_id');
        $userId = $request->user()->id;

        $existing = ForumVote::query()->where('thread_id', $threadId)->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
        } else {
            ForumVote::create(['thread_id' => $threadId, 'user_id' => $userId]);
        }

        return redirect()->route('thread', ['id' => $threadId]);
    }
}
