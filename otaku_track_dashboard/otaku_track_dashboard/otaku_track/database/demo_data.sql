-- DATA DEMO (opsional) untuk mengisi dashboard admin saat uji coba atau presentasi.
-- Semua akun demo berawalan `demo_` dengan password `Demo123!`.
-- Hapus lagi dengan database/demo_cleanup.sql sebelum data asli dipakai.
USE otaku_track;

INSERT IGNORE INTO users (username, email, password_hash, role, created_at) VALUES
('demo_sakura', 'sakura@demo.local', '$2y$10$NdT1uSeSyUTFf4HUpgFosOixUbTxPz9alXGQDtdRyrFGo6FCfIKfK', 'user', NOW() - INTERVAL 13 DAY),
('demo_budi', 'budi@demo.local', '$2y$10$NdT1uSeSyUTFf4HUpgFosOixUbTxPz9alXGQDtdRyrFGo6FCfIKfK', 'user', NOW() - INTERVAL 11 DAY),
('demo_rina', 'rina@demo.local', '$2y$10$NdT1uSeSyUTFf4HUpgFosOixUbTxPz9alXGQDtdRyrFGo6FCfIKfK', 'user', NOW() - INTERVAL 9 DAY),
('demo_kenji', 'kenji@demo.local', '$2y$10$NdT1uSeSyUTFf4HUpgFosOixUbTxPz9alXGQDtdRyrFGo6FCfIKfK', 'user', NOW() - INTERVAL 6 DAY),
('demo_dewi', 'dewi@demo.local', '$2y$10$NdT1uSeSyUTFf4HUpgFosOixUbTxPz9alXGQDtdRyrFGo6FCfIKfK', 'user', NOW() - INTERVAL 3 DAY),
('demo_arif', 'arif@demo.local', '$2y$10$NdT1uSeSyUTFf4HUpgFosOixUbTxPz9alXGQDtdRyrFGo6FCfIKfK', 'user', NOW() - INTERVAL 1 DAY);

INSERT IGNORE INTO media_lists (user_id, media_type, external_id, title, status, score, progress, created_at) VALUES
((SELECT id FROM users WHERE username='demo_sakura'), 'anime', 5114, 'Fullmetal Alchemist: Brotherhood', 'completed', 9.5, 64, NOW() - INTERVAL 12 DAY),
((SELECT id FROM users WHERE username='demo_sakura'), 'anime', 52991, 'Sousou no Frieren', 'watching', 9.0, 18, NOW() - INTERVAL 5 DAY),
((SELECT id FROM users WHERE username='demo_sakura'), 'manga', 2, 'Berserk', 'reading', 9.8, 120, NOW() - INTERVAL 8 DAY),
((SELECT id FROM users WHERE username='demo_budi'), 'anime', 5114, 'Fullmetal Alchemist: Brotherhood', 'completed', 9.0, 64, NOW() - INTERVAL 10 DAY),
((SELECT id FROM users WHERE username='demo_budi'), 'anime', 9253, 'Steins;Gate', 'completed', 9.5, 24, NOW() - INTERVAL 9 DAY),
((SELECT id FROM users WHERE username='demo_budi'), 'anime', 16498, 'Shingeki no Kyojin', 'dropped', 6.0, 12, NOW() - INTERVAL 7 DAY),
((SELECT id FROM users WHERE username='demo_rina'), 'anime', 9253, 'Steins;Gate', 'plan_to_watch', NULL, 0, NOW() - INTERVAL 8 DAY),
((SELECT id FROM users WHERE username='demo_rina'), 'anime', 44511, 'Chainsaw Man', 'watching', 8.0, 7, NOW() - INTERVAL 6 DAY),
((SELECT id FROM users WHERE username='demo_rina'), 'manga', 656, 'Vagabond', 'plan_to_read', NULL, 0, NOW() - INTERVAL 5 DAY),
((SELECT id FROM users WHERE username='demo_kenji'), 'anime', 1, 'Cowboy Bebop', 'completed', 8.5, 26, NOW() - INTERVAL 5 DAY),
((SELECT id FROM users WHERE username='demo_kenji'), 'anime', 19, 'Monster', 'on_hold', 8.0, 30, NOW() - INTERVAL 4 DAY),
((SELECT id FROM users WHERE username='demo_kenji'), 'anime', 5114, 'Fullmetal Alchemist: Brotherhood', 'plan_to_watch', NULL, 0, NOW() - INTERVAL 3 DAY),
((SELECT id FROM users WHERE username='demo_dewi'), 'anime', 52991, 'Sousou no Frieren', 'completed', 9.5, 28, NOW() - INTERVAL 2 DAY),
((SELECT id FROM users WHERE username='demo_dewi'), 'anime', 21, 'One Piece', 'watching', 8.5, 350, NOW() - INTERVAL 2 DAY),
((SELECT id FROM users WHERE username='demo_arif'), 'anime', 44511, 'Chainsaw Man', 'completed', 8.0, 12, NOW() - INTERVAL 1 DAY),
((SELECT id FROM users WHERE username='demo_arif'), 'manga', 2, 'Berserk', 'plan_to_read', NULL, 0, NOW() - INTERVAL 0 DAY);

INSERT INTO forum_threads (category_id, user_id, title, body, is_hidden, is_locked, created_at) VALUES
((SELECT id FROM forum_categories WHERE name='Recommendations'), (SELECT id FROM users WHERE username='demo_kenji'), 'Rekomendasi anime misteri selain Monster?', 'Habis tamat Monster dan masih penasaran. Ada rekomendasi yang atmosfernya mirip?', 0, 0, NOW() - INTERVAL 5 DAY),
((SELECT id FROM forum_categories WHERE name='Release Discussion'), (SELECT id FROM users WHERE username='demo_dewi'), 'Diskusi episode terbaru Frieren', 'Bagian flashback di episode ini benar-benar bikin merinding. Menurut kalian?', 0, 0, NOW() - INTERVAL 2 DAY),
((SELECT id FROM forum_categories WHERE name='Anime'), (SELECT id FROM users WHERE username='demo_budi'), 'Teori akhir Steins;Gate', 'Kalau Okabe tidak kembali ke garis dunia awal, apa yang akan berubah?', 0, 1, NOW() - INTERVAL 8 DAY),
((SELECT id FROM forum_categories WHERE name='Manga'), (SELECT id FROM users WHERE username='demo_sakura'), 'Urutan baca Berserk yang ideal', 'Sebaiknya mulai dari Golden Age atau langsung dari awal volume 1?', 0, 0, NOW() - INTERVAL 10 DAY),
((SELECT id FROM forum_categories WHERE name='Site Feedback'), (SELECT id FROM users WHERE username='demo_rina'), 'Fitur filter genre di pencarian', 'Bisa ditambah filter lebih dari satu genre sekaligus?', 0, 0, NOW() - INTERVAL 4 DAY),
((SELECT id FROM forum_categories WHERE name='General'), (SELECT id FROM users WHERE username='demo_arif'), 'Thread contoh yang disembunyikan', 'Contoh thread yang sedang disembunyikan moderator.', 1, 0, NOW() - INTERVAL 1 DAY);

INSERT INTO forum_replies (thread_id, user_id, body, is_hidden, created_at) VALUES
((SELECT id FROM forum_threads WHERE title='Rekomendasi anime misteri selain Monster?'), (SELECT id FROM users WHERE username='demo_sakura'), 'Coba Paranoia Agent dan Death Note, atmosfernya cocok.', 0, NOW() - INTERVAL 4 DAY),
((SELECT id FROM forum_threads WHERE title='Rekomendasi anime misteri selain Monster?'), (SELECT id FROM users WHERE username='demo_budi'), 'Ditambah Pluto kalau suka cerita yang pelan tapi dalam.', 0, NOW() - INTERVAL 4 DAY),
((SELECT id FROM forum_threads WHERE title='Diskusi episode terbaru Frieren'), (SELECT id FROM users WHERE username='demo_rina'), 'Setuju, musiknya juga pas banget di adegan itu.', 0, NOW() - INTERVAL 1 DAY),
((SELECT id FROM forum_threads WHERE title='Teori akhir Steins;Gate'), (SELECT id FROM users WHERE username='demo_kenji'), 'Menurutku garis dunia baru tetap punya konsekuensi kecil.', 0, NOW() - INTERVAL 7 DAY),
((SELECT id FROM forum_threads WHERE title='Teori akhir Steins;Gate'), (SELECT id FROM users WHERE username='demo_dewi'), 'Balasan contoh yang disembunyikan moderator.', 1, NOW() - INTERVAL 6 DAY),
((SELECT id FROM forum_threads WHERE title='Urutan baca Berserk yang ideal'), (SELECT id FROM users WHERE username='demo_arif'), 'Mulai dari volume 1 saja, Golden Age jadi lebih terasa.', 0, NOW() - INTERVAL 9 DAY),
((SELECT id FROM forum_threads WHERE title='Fitur filter genre di pencarian'), (SELECT id FROM users WHERE username='demo_sakura'), 'Dukung! Kadang butuh kombinasi seperti Aksi + Fantasi.', 0, NOW() - INTERVAL 3 DAY),
((SELECT id FROM forum_threads WHERE title='Fitur filter genre di pencarian'), (SELECT id FROM users WHERE username='demo_budi'), 'Mungkin bisa pakai checkbox genre di sidebar.', 0, NOW() - INTERVAL 0 DAY);

INSERT INTO watch_links (external_id, media_type, provider, url, created_by, created_at) VALUES
(5114, 'anime', 'Crunchyroll', 'https://www.crunchyroll.com/', (SELECT id FROM users WHERE username='admin'), NOW() - INTERVAL 6 DAY),
(9253, 'anime', 'Netflix', 'https://www.netflix.com/', (SELECT id FROM users WHERE username='admin'), NOW() - INTERVAL 4 DAY),
(52991, 'anime', 'Crunchyroll', 'https://www.crunchyroll.com/', (SELECT id FROM users WHERE username='admin'), NOW() - INTERVAL 2 DAY),
(2, 'manga', 'VIZ Media', 'https://www.viz.com/', (SELECT id FROM users WHERE username='admin'), NOW() - INTERVAL 1 DAY);
