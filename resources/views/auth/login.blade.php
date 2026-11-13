@extends('layouts.app')

@section('content')
<div class="container py-5"><div class="auth-card mx-auto">
<h2 class="fw-bold">Welcome back</h2><p class="text-secondary">Login ke Otaku Track.</p>
<form method="post" action="{{ route('login.post') }}">
@csrf
<label class="form-label">Username / Email</label><input name="login" class="form-control mb-3" required>
<label class="form-label">Password</label><input name="password" type="password" class="form-control mb-3" required>
<button class="btn btn-primary w-100">Login</button>
</form>
<div class="d-flex justify-content-between mt-3 small"><a href="{{ route('register') }}">Buat akun</a><a href="{{ route('forgot') }}">Forgot password?</a></div>
</div></div>
@endsection
