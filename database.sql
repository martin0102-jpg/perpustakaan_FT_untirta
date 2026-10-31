-- ===== Database Perpustakaan Kampus =====
-- Import lewat phpMyAdmin (tab Import). Di InfinityFree, buat database dulu
-- dari panel hosting, pilih database-nya, lalu import file ini
-- (hapus baris CREATE DATABASE / USE di bawah jika perlu).

CREATE DATABASE IF NOT EXISTS perpustakaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpustakaan;

CREATE TABLE users (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nama      VARCHAR(100) NOT NULL,
    username  VARCHAR(50)  NOT NULL UNIQUE,          -- NIM (mahasiswa) atau username (admin)
    password  VARCHAR(255) NOT NULL,                 -- hasil password_hash()
    role      ENUM('admin','mahasiswa') NOT NULL DEFAULT 'mahasiswa'
) ENGINE=InnoDB;

CREATE TABLE buku (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    judul     VARCHAR(200) NOT NULL,
    penulis   VARCHAR(150) NOT NULL,
    penerbit  VARCHAR(150) NOT NULL DEFAULT '',
    tahun     SMALLINT     NOT NULL,
    isbn      VARCHAR(20)  NOT NULL DEFAULT '',
    stok      INT          NOT NULL DEFAULT 0,
    CONSTRAINT chk_stok CHECK (stok >= 0)
) ENGINE=InnoDB;

CREATE TABLE kunjungan (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT  NOT NULL,
    tanggal      DATE NOT NULL,
    jam_submit   TIME NOT NULL,
    status       ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    verified_by  INT  NULL,
    verified_at  DATETIME NULL,
    FOREIGN KEY (user_id)     REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_kunjungan_status (status)
) ENGINE=InnoDB;

CREATE TABLE peminjaman (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT  NOT NULL,
    buku_id        INT  NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by    INT  NULL,
    approved_at    DATETIME NULL,
    FOREIGN KEY (user_id)     REFERENCES users(id),
    FOREIGN KEY (buku_id)     REFERENCES buku(id),   -- RESTRICT: buku beriwayat tidak bisa dihapus
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_peminjaman_status (status),
    INDEX idx_peminjaman_user (user_id, status)
) ENGINE=InnoDB;

CREATE TABLE pengembalian (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id   INT  NOT NULL UNIQUE,            -- satu peminjaman = satu pengembalian
    tanggal_kembali DATE NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by     INT  NULL,
    approved_at     DATETIME NULL,
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id),
    FOREIGN KEY (approved_by)   REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pengembalian_status (status)
) ENGINE=InnoDB;

-- ===== Data contoh =====
-- Admin     : username admin      / password admin123
-- Mahasiswa : NIM 3337220001      / password mahasiswa123  (juga 3337220002)
-- GANTI password ini setelah deploy!
INSERT INTO users (nama, username, password, role) VALUES
('Petugas Perpustakaan', 'admin',      '$2y$10$y3aIbsOwsGO2KVaFf/PNFeB7y0RMIu4lglDlUhF54iMJ8eyTodyn2', 'admin'),
('Andi Pratama',         '3337220001', '$2y$10$JUe1Mxm2Z2JYSLF.yFSEl.o69R1zCJDXVL8YBQln/TdOpPz8WsW8u',   'mahasiswa'),
('Siti Rahmawati',       '3337220002', '$2y$10$JUe1Mxm2Z2JYSLF.yFSEl.o69R1zCJDXVL8YBQln/TdOpPz8WsW8u',   'mahasiswa');

INSERT INTO buku (judul, penulis, penerbit, tahun, isbn, stok) VALUES
('Sistem Basis Data',            'Abraham Silberschatz', 'McGraw-Hill', 2019, '9781260084504', 5),
('Dasar-Dasar Pemrograman Web',  'Budi Raharjo',         'Informatika', 2020, '9786026231234', 3),
('Algoritma dan Struktur Data',  'Rinaldi Munir',        'Informatika', 2018, '9786026231111', 4),
('Jaringan Komputer',            'Andrew Tanenbaum',     'Pearson',     2021, '9780132126953', 2);
