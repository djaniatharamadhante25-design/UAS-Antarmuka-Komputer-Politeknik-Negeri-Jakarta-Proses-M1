-- Jalankan ini kalau database sudah terlanjur di-import dari schema.sql versi lama
-- dan akun admin tidak bisa login. Setelah ini: admin / Admin123!
USE otaku_track;

INSERT INTO users (username, email, password_hash, role)
VALUES ('admin', 'admin@otakutrack.local', '$2y$10$pFz1acL/Rpj5XSOCwwkzf.k7hA7/QTFwwM2LM9vd9PEZy3ZHbvO4O', 'admin')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin';
