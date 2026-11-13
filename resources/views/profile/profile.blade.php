@extends('layouts.app')

@php
    /** @var \App\Models\User $u */
    $u = $u;
@endphp

@section('content')
<div class="container py-5"><div class="profile-head content-card mb-4"><div class="avatar">{{ strtoupper(substr($u->username, 0, 1)) }}</div><div><h2 class="mb-1">{{ $u->username }}</h2><p class="text-secondary mb-0">{{ $u->bio ?: 'Anime & manga enthusiast.' }}</p></div></div>
<div class="row g-3 mb-4">@foreach (['watching' => 'Watching', 'completed' => 'Completed', 'plan_to_watch' => 'Plan to Watch', 'on_hold' => 'On Hold', 'dropped' => 'Dropped'] as $k => $v)<div class="col-6 col-md"><div class="stat-card"><strong>{{ (int) ($stats[$k] ?? 0) }}</strong><span>{{ $v }}</span></div></div>@endforeach</div>
<div class="content-card"><div class="d-flex justify-content-between"><h4>My List</h4><span class="text-secondary">{{ $items->count() }} items</span></div>
<div class="table-responsive mt-3"><table class="table table-dark table-hover align-middle"><thead><tr><th>Title</th><th>Status</th><th>Score</th><th>Progress</th><th></th></tr></thead><tbody>@foreach ($items as $i)<tr><td><img class="table-cover" src="{{ App\Support\OtakuHelper::imageUrl($i->image_url) }}"> {{ $i->title }}</td><td>{{ App\Support\OtakuHelper::formatStatus($i->status) }}</td><td>{{ $i->score ?? '-' }}</td><td>{{ (int) $i->progress }}</td><td><form method="post" action="{{ route('media_list.delete') }}">@csrf<input type="hidden" name="media_type" value="{{ $i->media_type }}"><input type="hidden" name="external_id" value="{{ (int) $i->external_id }}"><button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr>@endforeach</tbody></table></div>
</div></div>
@endsection
