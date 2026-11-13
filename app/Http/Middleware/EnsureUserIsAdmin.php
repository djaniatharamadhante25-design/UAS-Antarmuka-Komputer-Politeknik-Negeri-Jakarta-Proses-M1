<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti require_admin() dari repo asli: hanya user dengan role 'admin'
 * yang boleh masuk, selain itu 403.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            abort(403, '403 — Admin only.');
        }

        return $next($request);
    }
}
