-- Menghapus data dari demo_data.sql. Aman dijalankan berulang.
USE otaku_track;

-- Link demo (dibuat oleh admin, jadi dihapus berdasarkan isinya)
DELETE FROM watch_links
WHERE (external_id, media_type, provider) IN ((5114,'anime','Crunchyroll'),(9253,'anime','Netflix'),(52991,'anime','Crunchyroll'),(2,'manga','VIZ Media'))
  AND url IN ('https://www.crunchyroll.com/','https://www.netflix.com/','https://www.viz.com/');

-- Akun demo; list, thread, balasan, dan vote miliknya ikut terhapus lewat ON DELETE CASCADE
DELETE FROM users WHERE username LIKE 'demo\_%';
