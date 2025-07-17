-- Hapus database lama jika ada
DROP DATABASE IF EXISTS login_system_v2;

-- Buat database baru
CREATE DATABASE login_system_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Gunakan database
USE login_system_v2;

-- Buat tabel users dengan struktur yang lebih baik
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_locked BOOLEAN NOT NULL DEFAULT FALSE,
    login_attempts TINYINT NOT NULL DEFAULT 0,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Buat tabel untuk menyimpan log login
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert data admin (password: Admin123!)
INSERT INTO users (username, password_hash, full_name, email, role)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Administrator',
    'admin@example.com',
    'admin'
);

-- Insert data user contoh (password: Usti123@)
INSERT INTO users (username, password_hash, full_name, email)
VALUES (
    'zuprizal',
    '$2y$10$NlqZz5Xz5Xz5Xz5Xz5Xz.5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz',
    'Zuprizal Usti',
    'zuprizal@example.com'
);

-- Insert beberapa user tambahan
INSERT INTO users (username, password_hash, full_name, email)
VALUES 
    ('user1', '$2y$10$NlqZz5Xz5Xz5Xz5Xz5Xz.5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz', 'User Satu', 'user1@example.com'),
    ('user2', '$2y$10$NlqZz5Xz5Xz5Xz5Xz5Xz.5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz5Xz', 'User Dua', 'user2@example.com');