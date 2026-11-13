<!doctype html><html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Otaku Track Health</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<main class="container py-5">
    <h1>Otaku Track — Health Check</h1>
    <p class="text-secondary">Halaman ini dipakai untuk memastikan PHP, MySQL, cURL, dan Jikan benar-benar tersambung.</p>
    <div class="card bg-black border-secondary"><div class="card-body">
        <p>PHP: <b class="text-success">OK</b> {{ $phpOk }}</p>
        <p>cURL: <b class="{{ $curlOk ? 'text-success' : 'text-danger' }}">{{ $curlOk ? 'OK' : 'FAIL' }}</b></p>
        <p>MySQL: <b class="{{ $dbOk ? 'text-success' : 'text-danger' }}">{{ $dbOk ? 'OK' : 'FAIL' }}</b> {{ $dbError }}</p>
        <p>Jikan API: <b class="{{ $apiOk ? 'text-success' : 'text-danger' }}">{{ $apiOk ? 'OK' : 'FAIL' }}</b></p>
        @if ($apiError)
            <pre class="text-warning mb-0">{{ $apiError }}</pre>
        @endif
        @if ($apiOk)
            <hr>
            <p class="mb-2">Jikan sample:</p>
            <ul>
                @foreach ($sample as $a)
                    <li>{{ $a['title'] ?? 'Untitled' }} — {{ (string) ($a['score'] ?? 'N/A') }}</li>
                @endforeach
            </ul>
        @endif
        <p>AniList: <b class="{{ $aniOk ? 'text-success' : 'text-danger' }}">{{ $aniOk ? 'OK' : 'FAIL' }}</b></p>
        @if ($aniError)
            <pre class="text-warning mb-0">{{ $aniError }}</pre>
        @endif
        @if ($aniOk)
            <p class="mb-0">AniList fallback berhasil mengembalikan {{ count($aniSample) }} judul.</p>
        @endif
    </div></div>
    <a class="btn btn-primary mt-4" href="{{ route('home') }}">Kembali ke Home</a>
</main>
</body></html>
