CREATE DATABASE IF NOT EXISTS otaku_track
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE otaku_track;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    avatar_url VARCHAR(500) NULL,
    bio TEXT NULL,
    reset_token_hash VARCHAR(64) NULL,
    reset_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS media_lists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    media_type ENUM('anime','manga') NOT NULL,
    external_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    image_url VARCHAR(500) NULL,
    status ENUM('watching','completed','on_hold','dropped','plan_to_watch','reading','plan_to_read') NOT NULL,
    score DECIMAL(3,1) NULL,
    progress INT UNSIGNED DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_media (user_id, media_type, external_id),
    CONSTRAINT fk_media_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS watch_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id INT UNSIGNED NOT NULL,
    media_type ENUM('anime','manga') NOT NULL DEFAULT 'anime',
    provider VARCHAR(100) NOT NULL,
    url VARCHAR(1000) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_watch_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_threads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    external_id INT UNSIGNED NULL,
    media_type ENUM('anime','manga') NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_thread_category FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_thread_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reply_thread FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_reply_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vote (thread_id, user_id),
    CONSTRAINT fk_vote_thread FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_vote_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO forum_categories (name, description) VALUES
('General', 'Diskusi umum seputar anime, manga, dan komunitas.'),
('Anime', 'Diskusi judul, episode, karakter, dan teori anime.'),
('Manga', 'Diskusi manga, chapter, author, dan teori.'),
('Release Discussion', 'Diskusi rilisan dan episode/chapter terbaru.'),
('Recommendations', 'Rekomendasi anime atau manga.'),
('Site Feedback', 'Saran dan laporan bug untuk Otaku Track.');

-- Password admin demo: Admin123!
-- Hanya contoh untuk instalasi lokal. Sebaiknya buat user lewat Register lalu ubah role.
INSERT IGNORE INTO users (username, email, password_hash, role)
VALUES ('admin', 'admin@otakutrack.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa9n5eV6q9QkYf6Yj5q8z4Y8bQe', 'admin');
