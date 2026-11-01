# Perpus FT Untirta (PHP Native + MySQL)

Project mata kuliah Sistem Basis Data.

## Isi paket
- `database.sql` : struktur tabel + data contoh (untuk demo/presentasi).
- `database-bersih.sql` : struktur tabel + 1 akun admin saja (untuk dipakai sungguhan).
- Kedua file TIDAK membuat database; buat database kosong dulu, pilih, lalu Import.
  Tabel lama bernama sama akan dihapus dan dibuat ulang.

## Akun awal
| Role | Username / NIM | Password | Ada di |
|---|---|---|---|
| Admin | `admin` | `admin123` | kedua file |
| Mahasiswa | `3337220001`, `3337220002` | `mahasiswa123` | hanya `database.sql` |

**Ganti password admin setelah deploy:**
1. Di komputer (XAMPP), buka Command Prompt: `C:\xampp\php\php.exe -r "echo password_hash('PasswordBaruAnda', PASSWORD_DEFAULT);"`
2. Salin hasilnya (diawali `$2y$10$`), lalu di phpMyAdmin tab SQL jalankan:
   `UPDATE users SET password = 'HASIL_TADI' WHERE username = 'admin';`

## Menjalankan di XAMPP
1. Salin folder `perpustakaan` ke `C:\xampp\htdocs\`.
2. Start Apache + MySQL. Buka `http://localhost/phpmyadmin`, buat database `perpus_ft_untirta`
   (collation `utf8mb4_unicode_ci`), pilih database itu, tab **Import**, pilih `database.sql`.
3. `config/db.php` sudah cocok untuk XAMPP. Buka `http://localhost/perpustakaan`.

## Deploy ke InfinityFree (akun / domain baru)
1. Daftar di infinityfree.com, buat hosting account (pilih subdomain gratis atau domain sendiri).
2. Client Area -> **MySQL Databases** -> buat database. Catat: MySQL Host Name (`sqlXXX.infinityfree.com`),
   MySQL User Name (`if0_XXXXXXXX`), dan nama database lengkap (`if0_XXXXXXXX_namaanda`).
   Password MySQL = password akun hosting (vPanel).
3. Klik **phpMyAdmin** di database itu, tab **Import**, pilih `database-bersih.sql` (atau `database.sql` untuk demo).
4. Edit `config/db.php`: isi `$DB_HOST`, `$DB_USER`, `$DB_PASS`, `$DB_NAME`, dan set `BASE_URL` menjadi `''` (kosong).
5. Upload ISI folder `perpustakaan/` ke folder `htdocs/` (FileZilla/FTP, data ada di menu FTP Details).
6. Buka website, uji daftar + login, lalu ganti password admin.

## Alasan keputusan teknis (untuk dosen)
| Keputusan | Alasan |
|---|---|
| Prepared statement di semua query | Mencegah SQL injection. |
| `password_hash` / `password_verify` | Password tidak disimpan polos; bcrypt memakai salt dan sengaja lambat. |
| Satu tabel `users` + kolom `role` | Admin dan mahasiswa punya data login yang sama; dibedakan role. |
| Tabel `pengembalian` terpisah + `UNIQUE(peminjaman_id)` | Relasi 1:1 dijaga database, bukan hanya PHP. |
| Stok berubah hanya saat APPROVE, dalam transaksi | Status dan stok berubah bersamaan; gagal di tengah = rollback. |
| `SELECT ... FOR UPDATE` | Dua petugas menyetujui bersamaan tidak mengurangi stok dua kali. |
| `WHERE stok > 0` + `CHECK (stok >= 0)` | Stok tidak mungkin negatif (kode dan database). |
| FK `peminjaman.buku_id` (RESTRICT) | Buku beriwayat tidak bisa terhapus. |
| `UNIQUE(user_id, tanggal)` di `kunjungan` | Satu kunjungan per hari dijaga database, tahan klik ganda. |
| Pengembalian ditolak diajukan ulang dengan memakai ulang baris lama | Karena UNIQUE, tidak boleh ada baris kedua. |
| `user_id` dari `$_SESSION`, role registrasi di-hardcode | Identitas dan role tidak bisa dipalsukan lewat form. |
| `session_regenerate_id`, cookie httponly + samesite | Mencegah session fixation dan pencurian cookie. |
| Token CSRF di semua form POST | Mencegah form dikirim dari situs lain. |
| Pembatas login (5 salah = tunggu 60 detik) | Memperlambat tebak password. |
| `htmlspecialchars` (fungsi `e()`) | Mencegah XSS. |
| Post-Redirect-Get + flash message | Refresh tidak mengirim ulang form. |
| Query di `functions/`, tampilan di halaman | Pemisahan logic dan tampilan. |
| Tanggal dari PHP `date()` (Asia/Jakarta) | Zona waktu konsisten di XAMPP maupun hosting. |
