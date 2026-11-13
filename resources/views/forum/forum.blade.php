@extends('layouts.app')

@section('content')
<div class="container py-5"><div class="d-flex justify-content-between align-items-end mb-4"><div><div class="eyebrow">COMMUNITY</div><h2 class="fw-bold">Forum</h2><p class="text-secondary">Temukan diskusi dan mulai thread baru.</p></div>@if (auth()->check())<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#threadModal">New Thread</button>@endif</div>
<div class="row g-3 mb-4">@foreach ($categories as $c)<div class="col-md-4"><div class="content-card"><h5>{{ $c->name }}</h5><p class="small text-secondary">{{ $c->description }}</p><span class="badge badge-soft">{{ (int) $c->thread_count }} threads</span></div></div>@endforeach</div>
<div class="content-card"><div class="forum-list">@foreach ($threads as $t)<a class="forum-row" href="{{ route('thread', ['id' => (int) $t->id]) }}"><div class="vote-box"><strong>{{ (int) ($t->vote_count ?? 0) }}</strong><small>votes</small></div><div class="flex-grow-1"><h5>{{ $t->title }}</h5><div class="small text-secondary">{{ $t->category->name ?? '' }} · by {{ $t->user->username ?? '' }} · {{ (int) ($t->reply_count ?? 0) }} replies</div></div></a>@endforeach</div></div>
</div>
@if (auth()->check())
<div class="modal fade" id="threadModal"><div class="modal-dialog"><div class="modal-content app-modal"><div class="modal-header"><h5>New Thread</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="post" action="{{ route('forum.thread.create') }}"><div class="modal-body">@csrf<label class="form-label">Category</label><select name="category_id" class="form-select mb-3">@foreach ($categories as $c)<option value="{{ (int) $c->id }}">{{ $c->name }}</option>@endforeach</select><input name="title" class="form-control mb-3" placeholder="Thread title" required><textarea name="body" class="form-control" rows="6" placeholder="Write your discussion..." required></textarea></div><div class="modal-footer"><button class="btn btn-primary">Create</button></div></form></div></div></div>
@endif
@endsection
