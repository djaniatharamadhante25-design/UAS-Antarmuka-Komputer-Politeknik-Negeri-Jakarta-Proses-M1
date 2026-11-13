<?php

namespace App\Http\Controllers;

use App\Models\MediaList;
use App\Support\OtakuHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MediaListController extends Controller
{
    /**
     * Aksi save_list dari index.php repo asli. Upsert pada kunci unik
     * (user_id, media_type, external_id) memakai updateOrCreate.
     */
    public function save(Request $request): RedirectResponse
    {
        $type = $request->input('media_type') === 'manga' ? 'manga' : 'anime';
        $malId = (int) $request->input('mal_id');
        $aniId = (int) $request->input('anilist_id');
        $externalId = $malId ?: $aniId;
        $title = trim((string) $request->input('title'));
        $image = trim((string) $request->input('image_url'));
        $status = trim((string) $request->input('status'));
        $score = $request->input('score') !== '' && $request->input('score') !== null ? (float) $request->input('score') : null;
        $progress = max(0, (int) $request->input('progress', 0));

        $allowed = ['watching', 'completed', 'on_hold', 'dropped', 'plan_to_watch', 'reading', 'plan_to_read'];
        if (! in_array($status, $allowed, true) || ! $externalId || $title === '') {
            return redirect()->route('home')->with('error', 'Data list tidak valid.');
        }

        MediaList::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'media_type' => $type,
                'external_id' => $externalId,
            ],
            [
                'title' => $title,
                'image_url' => $image,
                'status' => $status,
                'score' => $score,
                'progress' => $progress,
            ]
        );

        return redirect()->to(OtakuHelper::mediaDetailUrl($type, $malId, $aniId))
            ->with('success', 'List berhasil diperbarui.');
    }

    public function delete(Request $request): RedirectResponse
    {
        MediaList::query()
            ->where('user_id', $request->user()->id)
            ->where('media_type', $request->input('media_type'))
            ->where('external_id', (int) $request->input('external_id'))
            ->delete();

        return redirect()->route('profile')->with('success', 'Item dihapus dari list.');
    }
}
