<?php

namespace App\Http\Controllers;

use App\Services\JikanService;
use App\Support\LocalCatalog;
use Illuminate\View\View;

final class SearchController extends Controller
{
    public function __invoke(JikanService $api): View
    {
        $q = trim((string) request()->query('q', ''));
        $type = request()->query('type', 'anime') === 'manga' ? 'manga' : 'anime';
        $page = max(1, (int) request()->query('p', 1));
        $perPage = 28;

        $filters = [
            'format' => request()->query('format'),
            'type' => request()->query('format'),
            'status' => request()->query('status'),
            'rating' => request()->query('rating'),
            'min_score' => request()->query('min_score'),
            'order_by' => request()->query('order_by', 'popularity'),
            'sort' => request()->query('sort', 'desc'),
            'genre' => request()->query('genre'),
            'season' => request()->query('season'),
            'year' => request()->query('year'),
        ];

        if ($q !== '') {
            $results = $type === 'anime'
                ? $api->searchAnime($q, $filters, $page, $perPage)
                : $api->searchManga($q, $filters, $page, $perPage);
        } else {
            $filters['sort'] = request()->query('order_by') === 'score' ? 'SCORE_DESC' : 'POPULARITY_DESC';
            $results = $api->browse($type, $filters, $page, $perPage);
        }

        // Fallback ke database lokal jika API sedang tidak terjangkau atau kosong.
        if (empty($results)) {
            $results = LocalCatalog::titles($type, $q, $page, $perPage);
        }

        return view('anime.search', [
            'q' => $q,
            'type' => $type,
            'results' => $results,
            'filters' => $filters,
            'p' => $page,
            'perPage' => $perPage,
        ]);
    }
}
