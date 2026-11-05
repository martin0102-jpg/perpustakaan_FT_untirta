-- =====================================================================
-- Database Perpus FT Untirta  (VERSI BERSIH untuk produksi)
-- Isi: struktur tabel + 1 akun admin. Tanpa mahasiswa/buku contoh.
-- Cara pakai: pilih database di phpMyAdmin -> tab Import -> pilih file ini.
-- PERINGATAN: tabel lama bernama sama akan DIHAPUS lalu dibuat ulang.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pengembalian, peminjaman, kunjungan, buku, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nama      VARCHAR(100) NOT NULL,
    username  VARCHAR(50)  NOT NULL UNIQUE,          -- NIM (mahasiswa) atau username (admin)
    password  VARCHAR(255) NOT NULL,                 -- hasil password_hash()
    role      ENUM('admin','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
    wajib_ganti_password TINYINT(1) NOT NULL DEFAULT 0   -- 1 = harus ganti password (setelah direset petugas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE buku (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    judul     VARCHAR(200) NOT NULL,
    penulis   VARCHAR(150) NOT NULL,
    penerbit  VARCHAR(150) NOT NULL DEFAULT '',
    tahun     SMALLINT     NOT NULL,
    isbn      VARCHAR(20)  NOT NULL DEFAULT '',
    stok      INT          NOT NULL DEFAULT 0,
    gambar    VARCHAR(255) NULL,                 -- nama file cover (folder assets/uploads/buku)
    CONSTRAINT chk_stok CHECK (stok >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    UNIQUE KEY uq_kunjungan_hari (user_id, tanggal),  -- satu kunjungan per mahasiswa per hari
    INDEX idx_kunjungan_status_tgl (status, tanggal)  -- mempercepat peringkat bulanan
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE peminjaman (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT  NOT NULL,
    buku_id        INT  NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_jatuh_tempo DATE NULL,                   -- diisi saat peminjaman disetujui
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by    INT  NULL,
    approved_at    DATETIME NULL,
    FOREIGN KEY (user_id)     REFERENCES users(id),
    FOREIGN KEY (buku_id)     REFERENCES buku(id),   -- RESTRICT: buku beriwayat tidak bisa dihapus
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_peminjaman_status (status),
    INDEX idx_peminjaman_jatuh_tempo (status, tanggal_jatuh_tempo),
    INDEX idx_peminjaman_user (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pengembalian (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id   INT  NOT NULL UNIQUE,            -- satu peminjaman = satu pengembalian
    tanggal_kembali DATE NOT NULL,
    hari_terlambat  INT  NOT NULL DEFAULT 0,         -- dibekukan saat pengembalian disetujui
    denda           INT  NOT NULL DEFAULT 0,         -- dalam Rupiah, dibekukan saat disetujui
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by     INT  NULL,
    approved_at     DATETIME NULL,
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id),
    FOREIGN KEY (approved_by)   REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pengembalian_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin awal: username admin / password admin123  -> WAJIB diganti segera (lihat README).
INSERT INTO users (nama, username, password, role) VALUES
('Petugas Perpustakaan', 'admin', '$2y$10$y3aIbsOwsGO2KVaFf/PNFeB7y0RMIu4lglDlUhF54iMJ8eyTodyn2', 'admin');
