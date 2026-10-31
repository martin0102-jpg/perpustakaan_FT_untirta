# Website Perpustakaan Kampus (PHP Native + MySQL)

Project mata kuliah Sistem Basis Data.

## Akun contoh (dari database.sql)
| Role | Username / NIM | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Mahasiswa | `3337220001` | `mahasiswa123` |
| Mahasiswa | `3337220002` | `mahasiswa123` |

Ganti password setelah deploy.

## Menjalankan di XAMPP
1. Salin folder `perpustakaan` ke `C:\xampp\htdocs\`.
2. Start Apache + MySQL, buka `http://localhost/phpmyadmin`, tab **Import**, pilih `database.sql`.
3. `config/db.php` sudah cocok untuk XAMPP (user `root`, password kosong, `BASE_URL` = `/perpustakaan`).
4. Buka `http://localhost/perpustakaan`.

## Deploy ke InfinityFree
1. Panel hosting -> **MySQL Databases** -> buat database. Catat host (`sqlXXX.infinityfree.com`), user (`if0_...`), nama database.
2. Buka phpMyAdmin dari panel, pilih database tadi, **Import** `database.sql`.
   Hapus dulu dua baris `CREATE DATABASE ...` dan `USE perpustakaan;` di file SQL (InfinityFree tidak mengizinkan membuat database lewat SQL).
3. Edit `config/db.php`: isi `$DB_HOST`, `$DB_USER`, `$DB_PASS`, `$DB_NAME`, dan ubah `BASE_URL` menjadi `''` (jika diletakkan langsung di `htdocs`).
4. Upload semua isi folder ke `htdocs/` lewat File Manager atau FTP.

## Alasan keputusan teknis (untuk dosen)
| Keputusan | Alasan |
|---|---|
| Prepared statement di semua query | Mencegah SQL injection; data user tidak pernah digabung langsung ke string SQL. |
| `password_hash` / `password_verify` | Password tidak disimpan polos; bcrypt otomatis memakai salt dan lambat disengaja agar sulit di-brute-force. |
| Satu tabel `users` dengan kolom `role` | Admin dan mahasiswa punya data login yang sama; cukup dibedakan role. Menghindari tabel ganda. |
| Tabel `pengembalian` terpisah + `UNIQUE(peminjaman_id)` | Relasi 1:1 peminjaman-pengembalian dijaga oleh database, bukan hanya oleh kode PHP. |
| Stok berubah hanya saat APPROVE, dalam transaksi | Status dan stok harus berubah bersamaan (atomik). Jika gagal di tengah, `rollback` membatalkan semuanya. |
| `SELECT ... FOR UPDATE` | Mengunci baris agar dua petugas yang menekan Setujui bersamaan tidak mengurangi stok dua kali. |
| `UPDATE ... WHERE stok > 0` + `CHECK (stok >= 0)` | Stok tidak mungkin negatif, dijaga di kode dan di database. |
| FK `peminjaman.buku_id` (RESTRICT) | Buku yang sudah punya riwayat tidak bisa terhapus. Aturan ini dijaga di aplikasi dan di database. |
| Aturan 1 pending per mahasiswa | Dicek di `peminjaman_ajukan()` sebelum INSERT; peminjaman approved boleh banyak. |
| Pengembalian ditolak diajukan ulang | Karena `UNIQUE`, baris lama yang rejected dipakai ulang dan dikembalikan ke pending, bukan membuat baris baru. |
| `user_id` diambil dari `$_SESSION` | Mahasiswa tidak bisa memalsukan identitas lewat form. |
| `session_regenerate_id` saat login | Mencegah session fixation. |
| Token CSRF di semua form POST | Mencegah form dikirim dari situs lain atas nama user yang sedang login. |
| `htmlspecialchars` (fungsi `e()`) | Mencegah XSS saat menampilkan data. |
| Pola Post-Redirect-Get + flash message | Refresh halaman tidak mengirim ulang form (mencegah dobel submit). |
| Query di `functions/`, tampilan di halaman | Pemisahan logic dan tampilan: lebih mudah dibaca dan diuji. |
| Tanggal dari PHP (`date()`), bukan `NOW()` MySQL | Zona waktu konsisten (Asia/Jakarta) di XAMPP maupun hosting. |
| Satu kunjungan per hari (tambahan) | Mencegah spam pengisian kunjungan; bisa dihapus di `kunjungan_tambah()` jika tidak diinginkan. |
