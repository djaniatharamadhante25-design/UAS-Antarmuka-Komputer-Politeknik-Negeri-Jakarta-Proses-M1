# Otaku Track

Aplikasi tracking anime & manga berbasis **Laravel 13**: katalog dari Jikan/AniList,
watchlist pribadi, forum komunitas, dan admin panel.

> **Catatan desain:** folder [`design/`](design) berisi screenshot dan mockup HTML
> (`design/index.html`) sebagai **referensi UI saja** — bukan target pixel-perfect.
> Tombol/toggle di bagian atas mockup hanya untuk berpindah halaman saat melihat
> referensi, bukan navigasi aplikasi. Poster dan detail judul diambil dari API asli.

---

## Stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Database | MySQL (Laragon) |
| View | Blade + Bootstrap 5 (CDN) + CSS token kustom |
| Aset | Vite 8 (`resources/css`, `resources/js`) |
| JS | Vanilla JS (navigasi mobile, bookmark, fallback API) |
| API eksternal | Jikan REST + AniList GraphQL, cache 6 jam + fallback lokal |

---

## Setup

Prasyarat: PHP 8.3+, Composer, Node.js + npm, MySQL.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Sesuaikan database di `.env` (default `otakutrackver5`), buat database-nya, lalu:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Buka <http://127.0.0.1:8000>.

### Akun admin

| Field | Nilai |
|---|---|
| Username | `admin` |
| Email | `admin@otakutrack.local` |
| Password | `Admin123!` |

Login bisa memakai **username atau email**.

---

## Struktur penting

```
app/
├── Http/
│   ├── Controllers/        # Home, Search, Detail, Auth, Profile, Forum, Admin, Health
│   └── Middleware/         # EnsureUserIsAdmin (alias: admin)
├── Models/                 # User, MediaList, CatalogTitle, Forum*, WatchLink
├── Services/               # JikanService, AniListService, ApiGuard, cache trait
└── Support/                # OtakuHelper, LocalCatalog, DetailNormalizer, AdminPresenter

database/
├── migrations/             # users, cache, jobs + tabel aplikasi (catalog_titles, media_lists, forum_*, watch_links)
└── seeders/                # DatabaseSeeder, ForumCategorySeeder, AdminUserSeeder

resources/
├── css/app.css             # token tema + layout + komponen + style admin
├── js/app.js               # navigasi mobile, bookmark, fallback katalog
└── views/
    ├── layouts/app.blade.php
    ├── auth/               # login, register, forgot, reset
    ├── anime/              # search, detail, detail_fallback
    ├── forum/              # forum, thread
    ├── profile/            # profile
    ├── admin/              # dashboard + sections/*
    └── errors/404.blade.php

routes/web.php              # semua route web
design/                     # referensi UI (screenshot + mockup HTML)
```

---

## Route utama

| Method | URL | Keterangan |
|---|---|---|
| GET | `/` | Home + hero carousel + 3 baris katalog |
| GET | `/search?type=anime\|manga` | Explore/search + filter |
| GET | `/detail?type=…&mal_id=…` | Detail judul (Jikan/AniList) |
| GET | `/login`, `/register`, `/forgot-password`, `/reset-password` | Auth |
| GET | `/profile` | Profil + watchlist (login) |
| GET | `/forum`, `/thread/{id}` | Forum & thread |
| GET | `/admin`, `/admin/{section}` | Admin (role admin) |
| GET | `/health`, `/up` | Diagnostik/health check |

Aksi tulis: `POST /media-list/save`, `/media-list/delete`, `/forum/thread`,
`/forum/reply`, `/forum/vote`, `/admin/action`, serta `POST /login`, `/register`,
`/forgot-password`, `/reset-password`, `/logout`.

---

## Data & fallback API

- `JikanService` & `AniListService` memakai Laravel HTTP Client.
- Response di-cache lewat `CachesApiResponse` (default 6 jam, data lama disimpan
  24 jam sebagai fallback saat API down).
- `ApiGuard` menjadi circuit breaker agar request tidak menunggu timeout berulang.
- Jika API tidak tersedia, `LocalCatalog` membaca tabel `catalog_titles`.
  Tabel ini opsional dan bisa diisi data sendiri.
- Detail judul juga punya fallback sisi browser (`detail_fallback.blade.php` →
  AniList langsung).

---

## Testing

```bash
php artisan test
```

Test memakai SQLite in-memory dan tidak memanggil API eksternal
(`Http::fake`). Cakupan: skema database, auth, forum, watchlist, admin, dan
halaman utama.

---

## Perintah berguna

```bash
php artisan migrate:fresh --seed   # reset database lokal + seed
npm run dev                        # Vite dev server
npm run build                      # build aset produksi
php artisan route:list             # daftar route
```
