# Dashboard admin (Kelola Data) - catatan perubahan

## Cara pasang
1. Database baru: import `database/schema.sql`. Login: `admin` / `Admin123!` (ganti setelah login pertama).
2. Database lama (admin tidak bisa login): jalankan `database/fix_admin_password.sql`.
3. Opsional, supaya dashboard terisi saat uji/presentasi: import `database/demo_data.sql`
   (akun `demo_*`, password `Demo123!`). Hapus dengan `database/demo_cleanup.sql`.

## Yang berubah
- Dashboard dengan sidebar: Ringkasan, Pengguna, Link tonton/baca, Kategori forum, Moderasi forum.
- Ringkasan: statistik + tren 7 hari, grafik 14 hari, status My List, judul paling banyak disimpan,
  aktivitas terbaru, dan pintasan "Perlu dicek".
- Tabel: cari, filter, pagination, konfirmasi hapus, empty state, tampilan mobile, mode gelap dan terang.
- Moderasi: thread (sembunyikan/kunci) dan balasan (sembunyikan); setelah aksi kembali ke filter yang sama.
- Perbaikan: hash admin di schema.sql tidak cocok dengan password-nya; admin tidak bisa menurunkan/menghapus
  akun sendiri atau admin terakhir; URL link hanya http/https; admin bisa membuka thread yang disembunyikan;
  DEBUG_MODE dimatikan (nyalakan lagi di config/config.php hanya saat debugging); zona waktu sesi MySQL
  disamakan dengan PHP.

## File baru
app/admin.php, views/admin/dashboard.php, views/admin/sections/*.php, assets/css/admin.css,
assets/js/admin.js, database/fix_admin_password.sql, database/demo_data.sql, database/demo_cleanup.sql
