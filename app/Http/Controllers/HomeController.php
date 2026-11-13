<?php

namespace App\Http\Controllers;

use App\Services\JikanService;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __invoke(JikanService $api): View
    {
        $popular = $api->popularAnime(12);
        $top = $api->topAnime(6);
        $manga = $api->popularManga(8);

        // API sedang gagal keduanya -> tampil fallback "API tidak tersedia".
        $apiOnline = ! empty($popular) || ! empty($top);

        return view('home', [
            'popular' => $popular,
            'top' => $top,
            'manga' => $manga,
            'apiOnline' => $apiOnline,
        ]);
    }
}
