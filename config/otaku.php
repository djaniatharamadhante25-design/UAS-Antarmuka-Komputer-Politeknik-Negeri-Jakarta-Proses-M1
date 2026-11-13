<?php

/*
 * Konfigurasi integrasi API eksternal Otaku Track.
 * Semua nilai berasal dari .env (lihat blok OTAKU_*) supaya bisa diubah tanpa edit kode.
 */

return [

    /*
     * Store cache yang dipakai untuk cache response API eksternal
     * dan circuit breaker ApiGuard. Default mengikuti CACHE_STORE.
     */
    'cache_store' => env('OTAKU_CACHE_STORE', env('CACHE_STORE', 'database')),

    'cache_prefix' => env('OTAKU_CACHE_PREFIX', 'otaku'),

    'jikan' => [
        'base_url' => env('JIKAN_BASE_URL', 'https://api.jikan.moe/v4'),
        'user_agent' => env('OTAKU_JIKAN_USER_AGENT', 'OtakuTrack/5.0-Laravel'),
    ],

    'anilist' => [
        'base_url' => env('ANILIST_BASE_URL', 'https://graphql.anilist.co'),
        'user_agent' => env('OTAKU_ANILIST_USER_AGENT', 'OtakuTrack/5.0-Laravel'),
    ],

    /*
     * Pengaturan performa API eksternal (detik).
     */
    'api' => [
        'connect_timeout' => (int) env('OTAKU_API_CONNECT_TIMEOUT', 3),  // maks. waktu menunggu koneksi
        'timeout' => (int) env('OTAKU_API_TIMEOUT', 8),                  // maks. waktu per request
        'anilist_timeout' => (int) env('OTAKU_ANILIST_TIMEOUT', 12),     // AniList diberi waktu lebih longgar
        'cache_ttl' => (int) env('OTAKU_API_CACHE_TTL', 21600),           // data fresh selama 6 jam
        'cache_retention' => (int) env('OTAKU_CACHE_RETENTION', 86400),  // data stale dipertahankan 1 hari sebagai fallback
        'down_cooldown' => (int) env('OTAKU_API_DOWN_COOLDOWN', 60),     // circuit breaker: setelah gagal, jangan dicoba selama 60 detik
    ],
];
