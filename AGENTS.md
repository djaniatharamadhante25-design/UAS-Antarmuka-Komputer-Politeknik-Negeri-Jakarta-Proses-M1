# AGENTS.md — Otaku Track

Panduan singkat untuk AI agent yang mengerjakan repo ini. Untuk konteks produk
dan roadmap lengkap, baca `PRD.md`; untuk kondisi kode saat ini, file ini yang
menjadi acuan utama.

## Konteks

- Aplikasi: tracking anime & manga (katalog API, watchlist, forum, admin panel).
- Stack aktual: **Laravel 13 + PHP 8.3 + Blade + Bootstrap 5 (CDN) + CSS token
  kustom + Vite 8 + MySQL (Laragon)**. Tidak memakai Alpine.js/Tailwind di view.
- Aset: `resources/css/app.css` (satu stylesheet, termasuk style admin) dan
  `resources/js/app.js`, dimuat lewat `@vite`.
- Desain referensi: folder `design/` — 9 screenshot + `design/index.html`.
  **Referensi UI saja, bukan target pixel-perfect.** Toggle di atas mockup hanya
  untuk berpindah halaman saat melihat referensi, bukan navigasi aplikasi.
  Poster & detail judul diambil dari API asli (Jikan/AniList).

## Aturan emas

1. **Tombol:** pakai kelas Bootstrap `.btn` + varian yang sudah ada
   (`btn-primary`, `btn-outline-light`, `btn-outline-danger`). Dilarang inline
   style pada tombol atau mencampur ukuran dalam satu row.
2. **Hero slider:** pertahankan overlay gradien ganda (kiri→kanan + bawah) dan
   text-shadow agar teks terbaca di backdrop terang/gelap.
3. **Konten:** sinopsis `line-clamp:3` + "Read more"; poster
   `aspect-ratio:2/3; object-fit:cover`; grid `auto-fill minmax(150px,1fr)`.
4. **Responsif:** 320–1440px rapi. Navbar mobile memakai drawer
   (`resources/js/app.js`, class `nav-open`); tabel memakai `.table-responsive`;
   touch target ≥ 44px.
5. **Token desain** ada di `resources/css/app.css` (`--bg`, `--panel`, `--blue`,
   dll). Jangan ubah tanpa alasan kuat.
6. **API eksternal** (Jikan/AniList) selalu lewat `JikanService` /
   `AniListService` + cache (`CachesApiResponse`, 6 jam) + fallback
   `LocalCatalog` (tabel `catalog_titles`). Jangan memanggil API langsung dari
   controller atau view (kecuali fallback browser di `detail_fallback`).

## Database

Model dan nama tabel harus konsisten:

| Model | Tabel |
|---|---|
| `User` | `users` |
| `MediaList` | `media_lists` |
| `CatalogTitle` | `catalog_titles` |
| `ForumCategory` | `forum_categories` |
| `ForumThread` | `forum_threads` |
| `ForumReply` | `forum_replies` |
| `ForumVote` | `forum_votes` |
| `WatchLink` | `watch_links` |

Jangan menambah tabel legacy baru tanpa memperbarui migration, model, seeder,
dan test skema (`tests/Feature/DatabaseSchemaTest.php`).

## Konvensi

- Controller tipis; logika data yang kompleks taruh di `app/Support` atau
  `app/Services`.
- Semua list memakai pagination/limit; hindari `::all()` pada data yang tumbuh.
- Setiap aksi tulis memberi flash message (`success`/`error`) dan memakai CSRF.
- Validasi input dilakukan di controller/service sebelum menulis database.
- Test: `php artisan test` harus hijau. Test tidak boleh memanggil jaringan
  (gunakan `Http::fake`).
- Commit: `feat(phase-N): deskripsi singkat` bila repo memakai Git.

## Urutan fase (roadmap PRD)

`0 Setup → 1 Design system → 2 Auth → 3 Katalog → 4 Watchlist → 5 Forum → 6 Admin → 7 QA`

Selesai fase = `php artisan test` hijau + tampilan dicek manual di browser.
