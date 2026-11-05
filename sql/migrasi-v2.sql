-- =====================================================================
-- MIGRASI v2 (fitur gambar buku + peringkat kunjungan)
-- Untuk database yang SUDAH berjalan dan berisi data. Data tidak hilang.
-- Jalankan SEKALI: phpMyAdmin -> pilih database -> tab SQL -> tempel -> Go.
-- Jika muncul "Duplicate column name 'gambar'", berarti sudah pernah dijalankan.
-- =====================================================================
ALTER TABLE buku ADD COLUMN gambar VARCHAR(255) NULL AFTER stok;
ALTER TABLE kunjungan ADD INDEX idx_kunjungan_status_tgl (status, tanggal);
