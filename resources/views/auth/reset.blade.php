@extends('layouts.app')

@section('content')
<div class="container py-5"><div class="auth-card mx-auto">
<h2 class="fw-bold">New password</h2>
<form method="post" action="{{ route('reset.post') }}">
@csrf
<input type="hidden" name="token" value="{{ $token }}">
<input name="password" type="password" class="form-control mb-3" minlength="6" required><button class="btn btn-primary w-100">Change Password</button>
</form>
</div></div>
@endsection
