@extends('layouts.app')

@section('content')
<div class="container py-5"><a class="small text-secondary" href="{{ route('forum') }}">← Back to forum</a><div class="content-card mt-3 mb-3"><div class="small text-primary">{{ $thread->category->name ?? '' }}</div><h1 class="h3 mt-2">{{ $thread->title }}</h1><div class="small text-secondary mb-4">by {{ $thread->user->username ?? '' }} · {{ $thread->created_at }}</div><div class="thread-body">{!! nl2br(e($thread->body)) !!}</div><div class="mt-4"><form method="post" action="{{ route('forum.vote') }}" class="d-inline">@csrf<input type="hidden" name="thread_id" value="{{ (int) $thread->id }}"><button class="btn btn-sm btn-outline-primary">▲ Upvote ({{ (int) $voteCount }})</button></form></div></div>
@foreach ($replies as $r)<div class="content-card mb-3"><div class="small text-secondary mb-2"><strong>{{ $r->user->username ?? '' }}</strong> · {{ $r->created_at }}</div>{!! nl2br(e($r->body)) !!}</div>@endforeach
@if (auth()->check())<div class="content-card"><h5>Reply</h5><form method="post" action="{{ route('forum.reply') }}">@csrf<input type="hidden" name="thread_id" value="{{ (int) $thread->id }}"><textarea name="body" class="form-control mb-3" rows="5" required></textarea><button class="btn btn-primary">Post Reply</button></form></div>@else<div class="alert alert-dark">Login untuk membalas.</div>@endif
</div>
@endsection
