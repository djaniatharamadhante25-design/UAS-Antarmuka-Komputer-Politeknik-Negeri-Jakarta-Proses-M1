<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Autentikasi (login via username atau email, reset token ditampilkan di halaman)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('forgot');
Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('forgot.post');
Route::get('/reset-password', [AuthController::class, 'showReset'])->name('reset');
Route::post('/reset-password', [AuthController::class, 'reset'])->name('reset.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Katalog (Jikan + AniList, fallback katalog lokal)
Route::get('/search', SearchController::class)->name('search');
Route::get('/detail', DetailController::class)->name('detail');

// List pribadi
Route::middleware('auth')->group(function () {
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::post('/media-list/save', [MediaListController::class, 'save'])->name('media_list.save');
    Route::post('/media-list/delete', [MediaListController::class, 'delete'])->name('media_list.delete');

    // Forum (thread, balasan, vote)
    Route::post('/forum/thread', [ForumController::class, 'createThread'])->name('forum.thread.create');
    Route::post('/forum/reply', [ForumController::class, 'reply'])->name('forum.reply');
    Route::post('/forum/vote', [ForumController::class, 'vote'])->name('forum.vote');
});

Route::get('/forum', [ForumController::class, 'index'])->name('forum');
Route::get('/thread/{id}', [ForumController::class, 'thread'])->whereNumber('id')->name('thread');

// Dashboard admin (Kelola Data)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin');
    Route::get('/admin/{section}', [AdminController::class, 'dashboard'])
        ->whereIn('section', AdminController::SECTIONS)
        ->name('admin.section');
    Route::post('/admin/action', [AdminController::class, 'handleAction'])->name('admin.action');
});

// Health check
Route::get('/health', HealthController::class)->name('health');

// Kompatibilitas bookmark lama /index.php?page=... → redirect ke rute baru
Route::get('/index.php', function () {
    $page = request()->query('page', 'home');
    $query = array_filter(request()->query(), fn ($k) => $k !== 'page', ARRAY_FILTER_USE_KEY);

    $map = [
        'home' => 'home',
        'login' => 'login',
        'register' => 'register',
        'forgot' => 'forgot',
        'reset' => 'reset',
        'search' => 'search',
        'detail' => 'detail',
        'profile' => 'profile',
        'forum' => 'forum',
        'health' => 'health',
        'thread' => 'thread',
        'admin' => 'admin',
    ];

    $route = $map[$page] ?? null;
    if (! $route) {
        abort(404);
    }

    return redirect()->route($route, $query === [] ? [] : $query);
})->name('legacy.page');
