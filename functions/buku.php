<?php
// ===== Fungsi query: BUKU =====

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

function buku_tambah(mysqli $conn, array $d): void {
    db_run($conn,
        "INSERT INTO buku (judul, penulis, penerbit, tahun, isbn, stok) VALUES (?, ?, ?, ?, ?, ?)",
        'sssisi', [$d['judul'], $d['penulis'], $d['penerbit'], (int)$d['tahun'], $d['isbn'], (int)$d['stok']]);
}

function buku_ubah(mysqli $conn, int $id, array $d): void {
    db_run($conn,
        "UPDATE buku SET judul = ?, penulis = ?, penerbit = ?, tahun = ?, isbn = ?, stok = ? WHERE id = ?",
        'sssisii', [$d['judul'], $d['penulis'], $d['penerbit'], (int)$d['tahun'], $d['isbn'], (int)$d['stok'], $id]);
}

// Apakah buku pernah muncul di riwayat peminjaman (status apa pun)?
function buku_punya_riwayat(mysqli $conn, int $id): bool {
    $r = db_one($conn, "SELECT COUNT(*) AS n FROM peminjaman WHERE buku_id = ?", 'i', [$id]);
    return (int)$r['n'] > 0;
}

// Hapus buku HANYA jika belum pernah ada riwayat peminjaman.
function buku_hapus(mysqli $conn, int $id): bool {
    if (buku_punya_riwayat($conn, $id)) {
        return false;
    }
    return db_run($conn, "DELETE FROM buku WHERE id = ?", 'i', [$id]) > 0;
}

function buku_hitung(mysqli $conn): int {
    return (int)db_one($conn, "SELECT COUNT(*) AS n FROM buku")['n'];
}
