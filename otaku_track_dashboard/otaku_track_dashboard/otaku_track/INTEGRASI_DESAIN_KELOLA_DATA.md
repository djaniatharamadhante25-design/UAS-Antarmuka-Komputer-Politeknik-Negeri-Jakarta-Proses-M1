# Integrasi Otaku Track + Kelola Data

## Prinsip
Konsep katalog Otaku Track tetap API-driven untuk anime dan manga. Database tidak dipakai untuk menyalin katalog API, melainkan untuk data aplikasi yang memang perlu dikelola:
- pengguna dan role;
- My List;
- link Watch / Read yang dimasukkan admin;
- kategori dan moderasi forum.

## Kelola Data Admin
Masuk sebagai admin lalu buka **Kelola Data** dari navbar atau `?page=admin`.

Menu yang tersedia:
- **Overview**: ringkasan jumlah data dan shortcut.
- **Pengguna**: tambah, edit, ubah role, dan hapus user.
- **Watch / Read Links**: tambah, edit, dan hapus link berdasarkan MAL ID + tipe media.
- **Kategori Forum**: tambah, edit, dan hapus kategori yang tidak memiliki thread.
- **Moderasi Forum**: hide/show serta lock/unlock thread.

## Database
Tidak ada perubahan schema wajib untuk fitur ini. Tabel yang sudah tersedia digunakan kembali: `users`, `watch_links`, dan `forum_categories`.

## Visual
Halaman admin menggunakan token warna, panel, tombol, tabel, dan responsive breakpoint yang sama dengan `assets/css/app.css`, sehingga modul Kelola Data tidak menjadi dashboard yang terpisah dari identitas Otaku Track.

## Catatan Integrasi Kelompok
Repository kelompok terdeteksi sebagai repository publik dan memiliki folder `UTS Antarmuka Komputer Kelompok 5/animelist`. Untuk pencocokan visual yang benar-benar pixel-perfect, source folder tersebut perlu tersedia sebagai file/zip lokal karena nested GitHub folder tidak seluruhnya dapat dibaca dalam environment ini. Implementasi saat ini sengaja tidak mengarang bagian desain yang belum dapat diverifikasi.
