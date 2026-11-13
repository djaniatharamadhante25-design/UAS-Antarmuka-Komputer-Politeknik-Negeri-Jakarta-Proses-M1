@php
    use App\Models\User;
    /** @var User|null $currentUser */
    $currentUser = auth()->user();
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Otaku Track{{ isset($title) ? ' — ' . $title : '' }}</title>
    <meta name="description" content="{{ $description ?? 'Otaku Track — katalog, watchlist, dan forum anime & manga.' }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>
<nav class="site-nav">
    <div class="container nav-inner">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark">O</span><span>Otaku Track</span></a>

        <div class="nav-links" id="primary-nav">
            <a href="{{ route('search', ['type' => 'anime']) }}">Explore</a>
            <a href="{{ route('forum') }}">Forum</a>
            <a href="{{ route('search', ['type' => 'manga']) }}">Manga</a>
        </div>

        <form class="nav-search" method="get" action="{{ route('search') }}">
            <input type="hidden" name="type" value="{{ request()->query('type', 'anime') === 'manga' ? 'manga' : 'anime' }}">
            <input name="q" value="{{ (string) request()->query('q', '') }}" placeholder="Search anime..." aria-label="Search anime">
            <button type="submit" aria-label="Cari"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <div class="nav-actions">
            <button class="icon-btn menu-btn" id="nav-toggle" type="button" aria-label="Buka menu" aria-controls="primary-nav" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
            @if ($currentUser)
                @if ($currentUser->role === 'admin')
                    <a class="admin-link" href="{{ route('admin') }}" title="Kelola data"><i class="fa-solid fa-sliders"></i><span>Kelola Data</span></a>
                @endif
                <a class="user-pill" href="{{ route('profile') }}"><span class="mini-avatar">{{ strtoupper(substr($currentUser->username, 0, 1)) }}</span>{{ $currentUser->username }}</a>
            @else
                <a class="login-link" href="{{ route('login') }}">Login</a>
                <a class="register-btn" href="{{ route('register') }}">Register</a>
            @endif
        </div>
    </div>
</nav>

<main>
    @if (session('success'))
        <div class="container pt-3"><div class="alert alert-success">{{ session('success') }}</div></div>
    @endif
    @if (session('error'))
        <div class="container pt-3"><div class="alert alert-danger">{{ session('error') }}</div></div>
    @endif
    @yield('content')
</main>

<footer class="footer py-5 mt-5">
    <div class="container d-flex justify-content-between flex-wrap gap-3">
        <div><strong>Otaku Track</strong><div class="small text-secondary">Anime & manga tracking project.</div></div>
        <div class="small text-secondary">Metadata powered by AniList + Jikan API.</div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@vite(['resources/js/app.js'])
@stack('scripts')
</body>
</html>
