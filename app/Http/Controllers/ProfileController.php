<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $items = $user->mediaLists()
            ->orderByDesc('updated_at')
            ->get();

        $stats = $user->mediaLists()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('profile.profile', [
            'u' => $user,
            'items' => $items,
            'stats' => $stats,
        ]);
    }
}
