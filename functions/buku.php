<?php
// ===== Fungsi query: BUKU (termasuk gambar cover) =====

// Validasi input form buku. Mengembalikan [data_bersih, daftar_error].
function buku_validasi(array $in): array {
    $d = [
        'judul'    => trim($in['judul'] ?? ''),
        'penulis'  => trim($in['penulis'] ?? ''),
        'penerbit' => trim($in['penerbit'] ?? ''),
        'tahun'    => trim($in['tahun'] ?? ''),
        'isbn'     => trim($in['isbn'] ?? ''),
        'stok'     => trim($in['stok'] ?? ''),
    ];
    $err = [];
    if ($d['judul'] === '')   $err[] = 'Judul wajib diisi.';
    if ($d['penulis'] === '') $err[] = 'Penulis wajib diisi.';
    if (!ctype_digit($d['tahun']) || (int)$d['tahun'] < 1000 || (int)$d['tahun'] > (int)date('Y') + 1) {
        $err[] = 'Tahun terbit tidak valid.';
    }
    if (!ctype_digit($d['stok'])) $err[] = 'Stok harus bilangan bulat 0 atau lebih.';
    return [$d, $err];
}

// ----- Gambar cover -----

function buku_gambar_dir(): string {
    return dirname(__DIR__) . '/assets/uploads/buku';
}

// URL gambar untuk tag <img>, atau null jika buku belum punya cover.
function buku_gambar_url(?string $nama): ?string {
    return $nama ? BASE_URL . '/assets/uploads/buku/' . rawurlencode($nama) : null;
}

// Memproses file upload. Mengembalikan [berhasil, nama_file | pesan_error].
// Tidak memilih file = berhasil dengan hasil null.
function buku_simpan_gambar(array $f): array {
    $kode = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($kode === UPLOAD_ERR_NO_FILE) return [true, null];
    if ($kode === UPLOAD_ERR_INI_SIZE || $kode === UPLOAD_ERR_FORM_SIZE) return [false, 'Ukuran gambar terlalu besar (maksimal 2 MB).'];
    if ($kode !== UPLOAD_ERR_OK) return [false, 'Upload gambar gagal. Coba lagi.'];
    if ($f['size'] > 2 * 1024 * 1024) return [false, 'Ukuran gambar maksimal 2 MB.'];

    // Cek ISI file yang sebenarnya (bukan hanya nama/ekstensi dari user).
    $info = @getimagesize($f['tmp_name']);
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($ekstensi[$info[2]])) return [false, 'Format gambar harus JPG, PNG, atau WebP.'];
    if ($info[0] > 4000 || $info[1] > 4000) return [false, 'Dimensi gambar maksimal 4000 piksel.'];

    $dir = buku_gambar_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return [false, 'Folder penyimpanan gambar tidak dapat dibuat.'];

    // Nama acak + ekstensi dari hasil deteksi: mencegah file berbahaya dan nama bentrok.
    $nama = bin2hex(random_bytes(8)) . '.' . $ekstensi[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nama)) return [false, 'Gagal menyimpan gambar.'];
    return [true, $nama];
}

// Hapus file gambar dari server (hanya nama dengan format buatan kita yang diterima).
function buku_hapus_gambar(?string $nama): void {
    if ($nama && preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $nama)) {
        @unlink(buku_gambar_dir() . '/' . $nama);
    }
}

// ----- Query -----

// Daftar buku, dengan pencarian opsional (judul / penulis / ISBN).
function buku_semua(mysqli $conn, string $cari = ''): array {
    if ($cari === '') {
        return db_all($conn, "SELECT * FROM buku ORDER BY judul");
    }
    $like = '%' . $cari . '%';
    return db_all($conn,
        "SELECT * FROM buku WHERE judul LIKE ? OR penulis LIKE ? OR isbn LIKE ? ORDER BY judul",
        'sss', [$like, $like, $like]);
}

// Buku yang stoknya masih ada (untuk dropdown peminjaman).
function buku_tersedia(mysqli $conn): array {
    return db_all($conn, "SELECT id, judul, penulis, stok FROM buku WHERE stok > 0 ORDER BY judul");
}

function buku_cari_id(mysqli $conn, int $id): ?array {
    return db_one($conn, "SELECT * FROM buku WHERE id = ?", 'i', [$id]);
}

// $d['gambar'] (opsional) berisi nama file cover.
function buku_tambah(mysqli $conn, array $d): void {
    db_run($conn,
        "INSERT INTO buku (judul, penulis, penerbit, tahun, isbn, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)",
        'sssisis', [$d['judul'], $d['penulis'], $d['penerbit'], (int)$d['tahun'], $d['isbn'], (int)$d['stok'], $d['gambar'] ?? null]);
}

function buku_ubah(mysqli $conn, int $id, array $d): void {
    db_run($conn,
        "UPDATE buku SET judul = ?, penulis = ?, penerbit = ?, tahun = ?, isbn = ?, stok = ?, gambar = ? WHERE id = ?",
        'sssisisi', [$d['judul'], $d['penulis'], $d['penerbit'], (int)$d['tahun'], $d['isbn'], (int)$d['stok'], $d['gambar'] ?? null, $id]);
}

// Apakah buku pernah muncul di riwayat peminjaman (status apa pun)?
function buku_punya_riwayat(mysqli $conn, int $id): bool {
    $r = db_one($conn, "SELECT COUNT(*) AS n FROM peminjaman WHERE buku_id = ?", 'i', [$id]);
    return (int)$r['n'] > 0;
}

// Hapus buku HANYA jika belum pernah ada riwayat peminjaman; file cover ikut dihapus.
function buku_hapus(mysqli $conn, int $id): bool {
    if (buku_punya_riwayat($conn, $id)) {
        return false;
    }
    $buku = buku_cari_id($conn, $id);
    try {
        $ok = db_run($conn, "DELETE FROM buku WHERE id = ?", 'i', [$id]) > 0;
    } catch (mysqli_sql_exception $e) {
        return false; // ditolak foreign key (ada peminjaman yang masuk bersamaan)
    }
    if ($ok && $buku) buku_hapus_gambar($buku['gambar'] ?? null);
    return $ok;
}

function buku_hitung(mysqli $conn): int {
    return (int)db_one($conn, "SELECT COUNT(*) AS n FROM buku")['n'];
}

// Buku terbaru untuk halaman depan. LIMIT dipaksa integer agar aman.
function buku_terbaru(mysqli $conn, int $n = 4): array {
    return db_all($conn, "SELECT judul, penulis, tahun, stok, gambar FROM buku ORDER BY id DESC LIMIT ?", 'i', [$n]);
}
