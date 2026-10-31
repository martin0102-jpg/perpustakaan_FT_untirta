<?php
// ===== Fungsi query: PENGEMBALIAN =====

// Peminjaman milik mahasiswa yang BOLEH diajukan pengembaliannya:
// sudah approved, dan belum punya pengembalian (atau pengembaliannya pernah ditolak).
function pengembalian_bisa_diajukan(mysqli $conn, int $userId): array {
    return db_all($conn,
        "SELECT p.id, p.tanggal_pinjam, b.judul, pg.status AS status_kembali
         FROM peminjaman p
         JOIN buku b ON b.id = p.buku_id
         LEFT JOIN pengembalian pg ON pg.peminjaman_id = p.id
         WHERE p.user_id = ? AND p.status = 'approved' AND (pg.id IS NULL OR pg.status = 'rejected')
         ORDER BY p.id DESC", 'i', [$userId]);
}

// Mahasiswa mengajukan pengembalian. Stok BELUM bertambah (baru saat approve).
function pengembalian_ajukan(mysqli $conn, int $userId, int $peminjamanId): array {
    // Pastikan peminjaman milik user ini dan sudah disetujui.
    $p = db_one($conn, "SELECT id FROM peminjaman WHERE id = ? AND user_id = ? AND status = 'approved'",
        'ii', [$peminjamanId, $userId]);
    if (!$p) {
        return [false, 'Peminjaman tidak valid.'];
    }
    try {
        $ada = db_one($conn, "SELECT id, status FROM pengembalian WHERE peminjaman_id = ?", 'i', [$peminjamanId]);
        if ($ada) {
            // UNIQUE: satu peminjaman hanya punya satu baris pengembalian.
            // Kalau yang lama DITOLAK, baris itu dipakai ulang (kembali ke pending).
            if ($ada['status'] !== 'rejected') {
                return [false, 'Pengembalian untuk peminjaman ini sudah diajukan.'];
            }
            db_run($conn,
                "UPDATE pengembalian SET status = 'pending', tanggal_kembali = ?, approved_by = NULL, approved_at = NULL WHERE id = ?",
                'si', [date('Y-m-d'), $ada['id']]);
        } else {
            db_run($conn,
                "INSERT INTO pengembalian (peminjaman_id, tanggal_kembali, status) VALUES (?, ?, 'pending')",
                'is', [$peminjamanId, date('Y-m-d')]);
        }
    } catch (mysqli_sql_exception $e) {
        // Jaga-jaga jika dua klik bersamaan menabrak constraint UNIQUE.
        return [false, 'Pengembalian untuk peminjaman ini sudah diajukan.'];
    }
    return [true, 'Pengajuan pengembalian terkirim, menunggu persetujuan petugas.'];
}

// Riwayat pengembalian milik mahasiswa.
function pengembalian_riwayat(mysqli $conn, int $userId): array {
    return db_all($conn,
        "SELECT pg.*, b.judul, p.tanggal_pinjam
         FROM pengembalian pg
         JOIN peminjaman p ON p.id = pg.peminjaman_id
         JOIN buku b ON b.id = p.buku_id
         WHERE p.user_id = ? ORDER BY pg.id DESC", 'i', [$userId]);
}

// Semua pengembalian (admin), filter status opsional.
function pengembalian_semua(mysqli $conn, string $status = ''): array {
    $sql = "SELECT pg.*, p.tanggal_pinjam, u.nama, u.username AS nim, b.judul, a.nama AS nama_petugas
            FROM pengembalian pg
            JOIN peminjaman p ON p.id = pg.peminjaman_id
            JOIN users u ON u.id = p.user_id
            JOIN buku b ON b.id = p.buku_id
            LEFT JOIN users a ON a.id = pg.approved_by";
    if ($status !== '') {
        return db_all($conn, $sql . " WHERE pg.status = ? ORDER BY pg.id DESC", 's', [$status]);
    }
    return db_all($conn, $sql . " ORDER BY pg.id DESC");
}

// Petugas APPROVE pengembalian: status berubah + stok bertambah 1 (satu transaksi).
function pengembalian_approve(mysqli $conn, int $id, int $adminId): array {
    mysqli_begin_transaction($conn);
    try {
        $pg = db_one($conn,
            "SELECT pg.id, pg.status, p.buku_id
             FROM pengembalian pg JOIN peminjaman p ON p.id = pg.peminjaman_id
             WHERE pg.id = ? FOR UPDATE", 'i', [$id]);
        if (!$pg || $pg['status'] !== 'pending') {
            mysqli_rollback($conn);
            return [false, 'Pengembalian tidak ditemukan atau sudah diproses.'];
        }
        db_run($conn, "UPDATE buku SET stok = stok + 1 WHERE id = ?", 'i', [$pg['buku_id']]);
        db_run($conn,
            "UPDATE pengembalian SET status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?",
            'isi', [$adminId, date('Y-m-d H:i:s'), $id]);
        mysqli_commit($conn);
        return [true, 'Pengembalian disetujui, stok buku bertambah 1.'];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return [false, 'Terjadi kesalahan saat memproses pengembalian.'];
    }
}

function pengembalian_reject(mysqli $conn, int $id, int $adminId): bool {
    return db_run($conn,
        "UPDATE pengembalian SET status = 'rejected', approved_by = ?, approved_at = ? WHERE id = ? AND status = 'pending'",
        'isi', [$adminId, date('Y-m-d H:i:s'), $id]) > 0;
}

function pengembalian_hitung_pending(mysqli $conn): int {
    return (int)db_one($conn, "SELECT COUNT(*) AS n FROM pengembalian WHERE status = 'pending'")['n'];
}
