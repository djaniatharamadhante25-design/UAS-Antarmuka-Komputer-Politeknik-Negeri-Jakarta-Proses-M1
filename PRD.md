# PRD — Otaku Track

> **Product Requirements Document** untuk AI Agent (opencode + 9router: Antigravity, KiroAI, DeepSeek, Gemini)
> Versi: 1.0 · Status: Ready for development

> **Catatan implementasi (update):** kode aktual memakai Laravel 13 + Blade +
> Bootstrap 5 + CSS token kustom + Vite (lihat `AGENTS.md`). Folder `design/`
> hanyalah **referensi arah UI**, bukan target pixel-perfect: mockup belum
> memuat poster/detail asli dari Jikan. Toggle halaman di `design/index.html`
> hanya alat bantu melihat referensi, bukan navigasi aplikasi. Bagian Tailwind/
> Alpine di dokumen ini adalah target awal, bukan kondisi kode saat ini.


---

## 1. Ringkasan Produk

**Otaku Track** adalah web aplikasi tracking anime & manga dengan katalog 1.000+ judul (Jikan/MyAnimeList + AniList API), watchlist pribadi 5 status, forum komunitas, dan admin panel.

- **Stack:** Laravel 11/12 + Blade + Alpine.js + Tailwind CSS v4, MySQL (Laragon), PHP 8.2+
- **Tema:** Dark-first "Midnight Navy" — bg `#070e1a`, panel `#101a2b`, primary `#3f83f8`, accent `#7c5cff`
- **Referensi visual:** folder `design/` (9 screenshot + `design/index.html`) — acuan arah UI, bukan target identik
- **Baselines:** mockup `design/index.html` (buka di browser; tab halaman + catatan UX hanya untuk referensi)

## 2. User & Role

| Role | Akses |
|---|---|
| Guest | Home, Explore, Detail, Login/Register |
| Member | + Watchlist, Rating, Profil, Forum (thread/reply/vote) |
| Admin | + Admin panel (users, links, kategori, moderasi) |

## 3. Design System (WAJIB konsisten — lihat `design/09-design-system.png`)

### 3.1 Token warna
```css
--bg:#070e1a; --bg-2:#0b1220; --panel:#101a2b; --panel-2:#0d1524;
--line:#253650; --text:#f5f7fb; --muted:#8fa0b8;
--blue:#3f83f8; --violet:#7c5cff; --gold:#ffc94f; --green:#28c840; --red:#ff5f57;
```

### 3.2 Tipografi
- **Space Grotesk** (700): judul halaman, angka statistik, score
- **Inter** (400–700): body, label, meta
- Skala: H1 24–38px · section 16px · body 12–13px · label meta 9.5–11px uppercase + `letter-spacing:.1em`

### 3.3 Aturan komponen (jawaban atas permintaan revisi)

**Tombol — konsistensi mutlak:**
- Semua tombol dibuat via Blade component `<x-button variant="primary|secondary|danger|icon" size="sm|md|lg">`.
- Ukuran terkunci: `sm` 28px, `md` 34px, `lg` 42px height; radius 8px; padding simetris.
- DILARANG: inline style pada tombol, atau mencampur size dalam satu row.
- Primary: gradien `#3f83f8→#2f6de0` + shadow `rgba(63,131,248,.35)`. Secondary: ghost border `#2a3b58`. Danger: border & teks merah redup.

**Hero slider — keterbacaan teks dijamin:**
- Semua slide WAJIB punya overlay ganda:
  1. `linear-gradient(90deg, rgba(6,13,25,.95) 10%, rgba(6,13,25,.55) 50%, rgba(6,13,25,.2) 80%)` (kiri→kanan)
  2. `linear-gradient(0deg, #070e1a 0%, transparent 45%)` (bawah)
- Judul tambah `text-shadow: 0 2px 12px rgba(0,0,0,.8)`.
- Konten hero max-width 560px, selalu di kiri.
- Scrim via CSS `::before/::after`, JANGAN mengedit gambar.

**Content full & tidak terpotong:**
- Sinopsis/deskripsi: `line-clamp:3` + link "Read more" (bukan `white-space:nowrap`).
- Poster: `aspect-ratio:2/3; object-fit:cover` (tanpa height fix).
- Grid card: `repeat(auto-fill, minmax(150px, 1fr))`, gap 16px.
- Container: max-width 1280px, padding horizontal 24px.
- Skeleton shimmer saat load data API.

**Responsif (layar sampai content):**
- Breakpoint: `≥1280 desktop · 768–1279 tablet · 480–767 mobile · <480 kecil`.
- Judul fluid: `clamp(1.75rem, 5vw, 3.5rem)`.
- Navbar mobile: search jadi icon (fullscreen search sheet), links jadi hamburger drawer (Alpine).
- Hero: `80vh → 60vh` mobile.
- Filter sidebar Explore → drawer/bottom-sheet di `<768px`.
- Tabel (Profile/Admin) → scroll-x dengan kolom pertama sticky, ATAU stack jadi card di mobile.
- Semua touch target ≥ 44px. Focus state: `outline 2px rgba(63,131,248,.5)`.

## 4. Halaman & Referensi Design

| # | Halaman | Route | Screenshot |
|---|---|---|---|
| 01 | Home (hero carousel + 3 rows) | `/` | `design/01-home.png` |
| 02 | Explore/Search (filter + grid + pagination) | `/explore` | `design/02-explore.png` |
| 03 | Detail anime/manga | `/anime/{id}`, `/manga/{id}` | `design/03-detail.png` |
| 04 | Login/Register/Forgot/Reset | `/login` dst. | `design/04-auth.png` |
| 05 | Forum (kategori + list thread) | `/forum` | `design/05-forum.png` |
| 06 | Thread detail + balasan | `/forum/thread/{id}` | `design/06-thread.png` |
| 07 | Profil + watchlist | `/profile` | `design/07-profile.png` |
| 08 | Admin panel (5 section) | `/admin/*` | `design/08-admin.png` |
| 09 | 404 | `/404` | ikuti tema: angka besar + tagline + tombol Home |
| — | Design system | — | `design/09-design-system.png` |

## 5. Fungsionalitas per Modul (Backend)

### Modul 1 — Auth & User
- Register/login/logout/forgot/reset (Laravel Breeze, Blade stack)
- Role admin/member (spatie/laravel-permission), middleware `role:admin`
- Profil: edit username, bio, upload avatar (validasi mimes + max 2MB)

### Modul 2 — Katalog (Integrasi API)
- Service class `JikanService` + `AniListService` via Laravel HTTP Client
- **Cache wajib:** `Cache::remember('anime:top', 21600, ...)` (6 jam) — anti rate-limit
- Search & filter server-side: q, type (anime/manga), format, genre, season, year, min_score, sort → pagination 24/halaman
- Detail: sinopsis, characters+VA, relations, studio, where-to-watch (dari tabel lokal `watch_links`)
- Fallback: jika API gagal → ambil dari tabel `media_items` lokal (seed 1.000 judul)
- Isi tabel fallback `catalog_titles` opsional (buat seeder/importer sendiri; struktur tabel sudah tersedia)

### Modul 3 — Watchlist (fitur inti)
- CRUD list per user: add/update/remove, status (watching/completed/plan_to_watch/on_hold/dropped)
- Progress eps/chapter + score user (0–10)
- Unique: `(user_id, media_type, external_id)`
- Statistik profil: count per status
- Policy: user hanya bisa ubah miliknya

### Modul 4 — Forum
- Thread: create (kategori + title + body), list, sort (terbaru/terpopuler/belum dijawab), pagination
- Reply: create + nested 1 level, vote up/down (thread & reply)
- Laporan user → masuk antrean moderasi admin
- Moderasi admin: hide/lock thread & reply

### Modul 5 — Admin
- Overview: stat (users, list items, threads, links) + grafik aktivitas 7 hari (Chart.js)
- Users: index (search+filter role), edit role, reset password, delete
- Watch links: CRUD platform (tampil di "Where to Watch" detail)
- Kategori forum: CRUD
- Moderasi: daftar thread/reply terlapor, aksi hide/lock/resolve
- Flash message konfirmasi semua aksi

### Non-fungsional
- CSRF semua form, Form Request validation, RateLimit API-search (60/menit)
- Semua query list pakai pagination (no all())
- SEO dasar: title, meta description per halaman

## 6. Struktur Scaffolding Laravel (revisi)

```
otaku-track/
├── app/
│   ├── Enums/                  # MediaStatus, MediaType, ThreadSort
│   ├── Events/
│   ├── Forms/                  # Form Requests (StoreWatchlistForm, ...)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── HomeController.php
│   │   │   ├── ExploreController.php      # search + filter + pagination
│   │   │   ├── Anime/DetailController.php
│   │   │   ├── Auth/                    (Breeze)
│   │   │   ├── Forum/
│   │   │   │   ├── ForumController.php  # index kategori+threads
│   │   │   │   ├── ThreadController.php # show, store
│   │   │   │   └── ReplyController.php
│   │   │   ├── Profile/ProfileController.php
│   │   │   ├── WatchlistController.php  # store/update/destroy
│   │   │   └── Admin/
│   │   │       ├── DashboardController.php
│   │   │       ├── UserController.php
│   │   │       ├── WatchLinkController.php
│   │   │       ├── CategoryController.php
│   │   │       └── ModerationController.php
│   │   └── Middleware/  (role, api-cache)
│   ├── Models/                 # User, MediaItem, Watchlist, Thread, Reply, Vote, Report, Category, WatchLink
│   ├── Policies/               # WatchlistPolicy, ThreadPolicy, ReplyPolicy
│   └── Services/               # JikanService, AniListService, CatalogService
│                                 (CatalogService = pintu masuk + fallback DB)
├── database/
│   ├── migrations/
│   │   ├── create_media_items_table.php       # id, external_id, media_type, title, image_url, score, year, format, season, description, json cache
│   │   ├── create_watchlists_table.php        # user_id, media_type, external_id, status, progress, score  [UNIQUE combo]
│   │   ├── create_categories_table.php
│   │   ├── create_threads_table.php           # user_id, category_id, title, body, is_hidden, is_locked, vote_count, reply_count
│   │   ├── create_replies_table.php           # thread_id, parent_id nullable, user_id, body
│   │   ├── create_votes_table.php             # user_id, votable morph, value ±1  [UNIQUE combo]
│   │   ├── create_reports_table.php           # reporter_id, reportable morph, reason, resolved
│   │   └── create_watch_links_table.php       # media_type, external_id nullable, platform, url, region
│   └── seeders/                # ForumCategorySeeder, AdminUserSeeder
├── resources/
│   ├── views/
│   │   ├── components/
│   │   │   ├── button.blade.php               # x-button (variant+size) — KUNCI KONSISTENSI
│   │   │   ├── media-card.blade.php           # poster + badge score + type
│   │   │   ├── chip.blade.php
│   │   │   ├── stat-card.blade.php
│   │   │   ├── filter-field.blade.php
│   │   │   ├── pagination.blade.php           # 1 2 3 … N
│   │   │   ├── vote-box.blade.php
│   │   │   └── empty-state.blade.php
│   │   ├── layouts/
│   │   │   ├── app.blade.php                  # navbar + footer + flash
│   │   │   ├── auth.blade.php                 # split-screen
│   │   │   └── admin.blade.php                # sidebar
│   │   ├── home/index.blade.php
│   │   ├── explore/index.blade.php
│   │   ├── detail/{anime,manga}.blade.php     # 2 kolom + hero overlap
│   │   ├── forum/{index,thread}.blade.php
│   │   ├── profile/index.blade.php
│   │   ├── admin/{overview,users,links,categories,moderation}.blade.php
│   │   └── errors/404.blade.php
│   ├── css/app.css                            # Tailwind v4 + token @theme
│   └── js/app.js                              # Alpine init + carousel + theme toggle
├── routes/web.php                             # lihat §6.1
└── tests/Feature/                             # WatchlistTest, ForumTest, ExploreTest
```

### 6.1 Routes (ringkas)
```
GET  /                          home
GET  /explore                   ?type&q&format&genre&season&year&min_score&sort&page
GET  /anime/{id} · /manga/{id}  detail (cached)
Auth: Breeze default
GET  /profile | PATCH /profile
POST /watchlist | PATCH /watchlist/{id} | DELETE /watchlist/{id}
GET  /forum | GET /forum/thread/{id} | POST /forum/thread | POST /forum/thread/{id}/reply
POST /vote/{type}/{id} | POST /report
Admin (role:admin, prefix /admin): GET overview, resource users/watch-links/categories, POST moderation/{action}
```

## 7. Pembagian Tugas AI Agent (sesuai urutan pengerjaan)

> Protocol: setiap agent mengerjakan 1 fase di branch sendiri (`feat/phase-N`); agent berikutnya merge & lanjut. Setiap fase WAJIB lolos `php artisan test` + tampilan dicek vs screenshot `design/`.

| Fase | Tugas | Agent yang disarankan | Deliverable |
|---|---|---|---|
| **0** | Setup: `laravel new otaku-track`, Breeze (Blade), Tailwind v4, spatie, migrasi+seeder lengkap, `CatalogService` interface kosong | **DeepSeek** (task deterministik & boilerplate) | Repo jalan, `/login` muncul, `php artisan migrate --seed` sukses |
| **1** | Design system: token Tailwind `@theme`, `x-button`/`x-media-card`/`x-chip`/`x-pagination`/`empty-state`, layout `app/auth/admin` (navbar+drawer mobile+theme toggle), error 404 | **Antigravity / Gemini** (kuat visual) | Semua komponen render sesuai `design/09-design-system.png`; cek mobile drawer |
| **2** | Modul Auth + Profil (edit bio/avatar) + role middleware | **KiroAI** (spec-driven) | Login/logout/register/reset jalan, profil editable, admin terdeteksi |
| **3** | Modul Katalog: `JikanService` + `AniListService` + cache 6 jam + fallback DB, Home (hero carousel overlay ganda + rows), Explore (filter+pagination), Detail 2 kolom + skeleton | **Gemini** (reasoning API/async/cache terbesar) | Home/Explore/Detail sesuai `design/01–03.png`; API down → tetap jalan via DB |
| **4** | Modul Watchlist: CRUD + 5 status + progress + score + statistik + tabel profil + policy | **DeepSeek** (CRUD presisi) | Add to list dari detail; profil sesuai `design/07.png` |
| **5** | Modul Forum: kategori, thread, reply nested, vote, report, sort, pagination | **KiroAI** | Forum & thread sesuai `design/05–06.png` |
| **6** | Admin panel: overview + Chart.js, users, watch-links, kategori, moderasi | **Antigravity** | Admin sesuai `design/08.png`; semua aksi ada flash message |
| **7** | QA: responsif semua device (320–1440px), aksesibilitas (focus, contrast, aria), Feature tests, perf (cache hit, N+1) | **DeepSeek + Gemini review** | Lighthouse ≥ 90, semua test hijau |

**Aturan untuk semua agent:**
1. Baca `PRD.md` + screenshot `design/` SEBELUM menulis kode.
2. Jangan ubah token design system (§3) tanpa alasan kuat.
3. Semua tombol lewat `<x-button>` — dilarang hardcode.
4. Hero overlay ganda WAJIB — teks harus terbaca di gambar apa pun.
5. Commit kecil per fitur, pesan: `feat(phase-N): ...`.

<!-- SECTION-7 -->

