-- ========================================================================
-- Otaku Track — Schema Tabel Katalog Lokal (1.000 Judul Anime & Manga)
-- ========================================================================

USE otaku_track;

CREATE TABLE IF NOT EXISTS catalog_titles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id INT UNSIGNED NOT NULL,
    media_type ENUM('anime','manga') NOT NULL,
    title VARCHAR(255) NOT NULL,
    title_japanese VARCHAR(255) NULL,
    format VARCHAR(50) NULL,
    status VARCHAR(50) NULL,
    score DECIMAL(3,1) NULL,
    season_year INT NULL,
    episodes INT UNSIGNED NULL,
    chapters INT UNSIGNED NULL,
    genres VARCHAR(255) NULL,
    image_url VARCHAR(500) NULL,
    banner_url VARCHAR(500) NULL,
    synopsis TEXT NULL,
    popularity INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_catalog_media (media_type, external_id),
    INDEX idx_media_type (media_type),
    INDEX idx_type_score (media_type, score),
    INDEX idx_type_popularity (media_type, popularity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Setelah tabel dibuat, Anda dapat mengisi 1.000 judul secara otomatis dengan:
-- 1. Jalankan di terminal: php otaku_track/database/seed_1000_titles.php
-- 2. Atau buka di browser: http://localhost/otaku_track_dashboard/otaku_track/database/seed_1000_titles.php
