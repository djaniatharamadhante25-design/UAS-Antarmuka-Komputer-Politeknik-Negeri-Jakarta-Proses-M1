<?php

namespace App\Http\Controllers;

use App\Services\AniListService;
use App\Services\JikanService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Health check — porting dari health.php repo asli:
 * cek PHP, ekstensi cURL, MySQL, dan kedua API eksternal.
 */
final class HealthController extends Controller
{
    public function __invoke(JikanService $api, AniListService $ani): View
    {
        $phpOk = PHP_VERSION;
        $curlOk = extension_loaded('curl');

        $dbOk = false;
        $dbError = '';
        try {
            DB::select('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        $sample = $api->popularAnime(3);
        $apiOk = count($sample) > 0;
        $apiError = $api->lastError();

        $aniSample = $ani->popularAnime(2);
        $aniOk = count($aniSample) > 0;
        $aniError = $ani->lastError();

        return view('health', [
            'phpOk' => $phpOk,
            'curlOk' => $curlOk,
            'dbOk' => $dbOk,
            'dbError' => $dbError,
            'sample' => $sample,
            'apiOk' => $apiOk,
            'apiError' => $apiError,
            'aniSample' => $aniSample,
            'aniOk' => $aniOk,
            'aniError' => $aniError,
        ]);
    }
}
