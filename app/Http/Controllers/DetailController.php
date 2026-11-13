<?php

namespace App\Http\Controllers;

use App\Models\MediaList;
use App\Models\WatchLink;
use App\Services\JikanService;
use App\Support\DetailNormalizer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DetailController extends Controller
{
    public function __invoke(JikanService $api): View
    {
        /** @var Request $request */
        $request = request();
        $type = $request->query('type', 'anime') === 'manga' ? 'manga' : 'anime';
        $legacyId = (int) $request->query('id', 0);
        $malId = (int) $request->query('mal_id', 0);
        $aniId = (int) $request->query('anilist_id', 0);
        $source = (string) $request->query('source', '');

        // Kompatibilitas URL lama yang memakai satu `id` untuk dua namespace.
        if ($malId < 1 && $aniId < 1 && $legacyId > 0) {
            if ($source === 'anilist') {
                $aniId = $legacyId;
            } else {
                $malId = $legacyId;
            }
        }

        $media = null;

        // 1. Kalau ada AniList ID, utamakan AniList GraphQL (cepat & lengkap).
        if ($aniId > 0) {
            $media = $api->detailByAniListId($type, $aniId);
        }

        // 2. Kalau belum ketemu, cari di AniList memakai MAL ID.
        if (! $media && $malId > 0) {
            $media = $api->aniListService()->detailByMalId(strtoupper($type), $malId);
        }

        // 3. Fallback ke Jikan (REST MyAnimeList).
        if (! $media && $malId > 0) {
            $media = $type === 'anime' ? $api->anime($malId) : $api->manga($malId);
        }

        if (! $media) {
            return view('anime.detail_fallback', [
                'type' => $type,
                'malId' => $malId,
                'aniId' => $aniId,
                'jikanError' => $api->lastError(),
                'aniListError' => $api->aniListService()->lastError(),
            ]);
        }

        $resolvedMalId = (int) ($media['mal_id'] ?? $media['idMal'] ?? $malId);
        $resolvedAniId = (int) ($media['anilist_id'] ?? 0);
        if ($resolvedAniId < 1 && isset($media['idMal'], $media['siteUrl'])) {
            $resolvedAniId = (int) ($media['id'] ?? $aniId);
        }
        $linkId = $resolvedMalId ?: $resolvedAniId;

        $recommendations = DetailNormalizer::recommendations($media, $type);
        if (! $recommendations && $resolvedMalId > 0) {
            $recommendations = DetailNormalizer::recommendations(
                [
                    'recommendations' => array_map(
                        static fn ($item) => ['entry' => $item],
                        $api->recommendations($resolvedMalId, $type)
                    ),
                ],
                $type
            );
        }

        $characters = DetailNormalizer::characters($media);
        $staff = DetailNormalizer::staff($media);
        $relations = DetailNormalizer::relations($media, $type);
        $trailer = DetailNormalizer::trailer($media);

        $watchLinks = [];
        $myList = null;
        $user = $request->user();

        try {
            $watchLinks = WatchLink::query()
                ->where('media_type', $type)
                ->where('external_id', $linkId)
                ->orderBy('provider')
                ->get()
                ->all();

            if ($user) {
                $myList = MediaList::query()
                    ->where('user_id', $user->id)
                    ->where('media_type', $type)
                    ->where('external_id', $linkId)
                    ->first();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return view('anime.detail', [
            'media' => $media,
            'type' => $type,
            'recommendations' => $recommendations,
            'watchLinks' => $watchLinks,
            'myList' => $myList,
            'characters' => $characters,
            'staff' => $staff,
            'relations' => $relations,
            'resolvedMalId' => $resolvedMalId,
            'resolvedAniId' => $resolvedAniId,
            'linkId' => $linkId,
            'trailer' => $trailer,
        ]);
    }
}
