-- =====================================================================
-- MIGRASI v3 (jatuh tempo + denda + wajib ganti password)
-- Untuk database yang SUDAH berjalan (sudah menjalankan migrasi-v2).
-- Data tidak hilang. Jalankan SEKALI: phpMyAdmin -> pilih database -> tab SQL -> Go.
-- Jika muncul "Duplicate column name", berarti sudah pernah dijalankan.
-- =====================================================================
ALTER TABLE users        ADD COLUMN wajib_ganti_password TINYINT(1) NOT NULL DEFAULT 0 AFTER role;
ALTER TABLE peminjaman   ADD COLUMN tanggal_jatuh_tempo DATE NULL AFTER tanggal_pinjam;
ALTER TABLE peminjaman   ADD INDEX idx_peminjaman_jatuh_tempo (status, tanggal_jatuh_tempo);
ALTER TABLE pengembalian ADD COLUMN hari_terlambat INT NOT NULL DEFAULT 0 AFTER tanggal_kembali;
ALTER TABLE pengembalian ADD COLUMN denda INT NOT NULL DEFAULT 0 AFTER hari_terlambat;

-- Peminjaman lama yang sudah disetujui diberi jatuh tempo = tanggal disetujui + 7 hari
-- (samakan angka 7 dengan LAMA_PINJAM_HARI di config/aturan.php).
-- Catatan: peminjaman lama yang sudah lewat 7 hari akan langsung tercatat terlambat.
UPDATE peminjaman
   SET tanggal_jatuh_tempo = DATE_ADD(DATE(approved_at), INTERVAL 7 DAY)
 WHERE status = 'approved' AND tanggal_jatuh_tempo IS NULL AND approved_at IS NOT NULL;
