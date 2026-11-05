<?php
// ===== Fungsi query: PEMINJAMAN =====

// Apakah mahasiswa masih punya peminjaman berstatus pending?
function peminjaman_punya_pending(mysqli $conn, int $userId): bool {
    $r = db_one($conn, "SELECT COUNT(*) AS n FROM peminjaman WHERE user_id = ? AND status = 'pending'", 'i', [$userId]);
    return (int)$r['n'] > 0;
}

// Mahasiswa mengajukan peminjaman. Stok BELUM berkurang di sini (baru saat approve).
// Aturan: maksimal 1 pending; banyak approved diperbolehkan.
function peminjaman_ajukan(mysqli $conn, int $userId, int $bukuId): array {
    if (peminjaman_punya_pending($conn, $userId)) {
        return [false, 'Masih ada pengajuan yang menunggu persetujuan. Tunggu diproses dulu.'];
    }
    // Aturan denda: selama masih ada buku yang terlambat, tidak boleh meminjam lagi.
    if (peminjaman_hitung_terlambat($conn, $userId) > 0) {
        return [false, 'Anda masih punya buku yang terlambat dikembalikan. Kembalikan dulu sebelum meminjam lagi.'];
    }
    $buku = db_one($conn, "SELECT stok FROM buku WHERE id = ?", 'i', [$bukuId]);
    if (!$buku) {
        return [false, 'Buku tidak ditemukan.'];
    }
    if ((int)$buku['stok'] < 1) {
        return [false, 'Stok buku sedang habis.'];
    }
    db_run($conn,
        "INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, status) VALUES (?, ?, ?, 'pending')",
        'iis', [$userId, $bukuId, date('Y-m-d')]);
    return [true, 'Pengajuan peminjaman terkirim, menunggu persetujuan petugas.'];
}

// Riwayat peminjaman milik mahasiswa + status pengembaliannya (jika ada).
function peminjaman_milik(mysqli $conn, int $userId): array {
    return db_all($conn,
        "SELECT p.*, b.judul, pg.status AS status_kembali, pg.tanggal_kembali, pg.hari_terlambat, pg.denda
         FROM peminjaman p
         JOIN buku b ON b.id = p.buku_id
         LEFT JOIN pengembalian pg ON pg.peminjaman_id = p.id
         WHERE p.user_id = ? ORDER BY p.id DESC", 'i', [$userId]);
}

// Semua peminjaman (admin). $status: pending/approved/rejected, atau 'terlambat'.
function peminjaman_semua(mysqli $conn, string $status = ''): array {
    $sql = "SELECT p.*, u.nama, u.username AS nim, b.judul, b.stok, a.nama AS nama_petugas,
                   pg.status AS status_kembali, pg.tanggal_kembali, pg.hari_terlambat, pg.denda
            FROM peminjaman p
            JOIN users u ON u.id = p.user_id
            JOIN buku b ON b.id = p.buku_id
            LEFT JOIN users a ON a.id = p.approved_by
            LEFT JOIN pengembalian pg ON pg.peminjaman_id = p.id";
    if ($status === 'terlambat') {
        return db_all($conn, $sql . " WHERE " . peminjaman_sql_terlambat() . " ORDER BY p.tanggal_jatuh_tempo ASC",
            's', [date('Y-m-d')]);
    }
    if ($status !== '') {
        return db_all($conn, $sql . " WHERE p.status = ? ORDER BY p.id DESC", 's', [$status]);
    }
    return db_all($conn, $sql . " ORDER BY p.id DESC");
}

// Petugas APPROVE: status berubah + stok berkurang 1, dalam satu TRANSAKSI.
// Jika salah satu langkah gagal, semuanya dibatalkan (rollback) agar data konsisten.
function peminjaman_approve(mysqli $conn, int $id, int $adminId): array {
    $jatuhTempo = date('Y-m-d', strtotime('+' . LAMA_PINJAM_HARI . ' days')); // batas kembali
    mysqli_begin_transaction($conn);
    try {
        // FOR UPDATE mengunci baris agar tidak diproses ganda oleh dua petugas bersamaan.
        $p = db_one($conn, "SELECT id, buku_id, status FROM peminjaman WHERE id = ? FOR UPDATE", 'i', [$id]);
        if (!$p || $p['status'] !== 'pending') {
            mysqli_rollback($conn);
            return [false, 'Peminjaman tidak ditemukan atau sudah diproses.'];
        }
        // "AND stok > 0" memastikan stok tidak pernah negatif.
        $n = db_run($conn, "UPDATE buku SET stok = stok - 1 WHERE id = ? AND stok > 0", 'i', [$p['buku_id']]);
        if ($n === 0) {
            mysqli_rollback($conn);
            return [false, 'Stok buku habis, peminjaman tidak dapat disetujui.'];
        }
        db_run($conn,
            "UPDATE peminjaman SET status = 'approved', approved_by = ?, approved_at = ?, tanggal_jatuh_tempo = ? WHERE id = ?",
            'issi', [$adminId, date('Y-m-d H:i:s'), $jatuhTempo, $id]);
        mysqli_commit($conn);
        return [true, 'Peminjaman disetujui, stok berkurang 1. Jatuh tempo: ' . tgl($jatuhTempo) . '.'];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return [false, 'Terjadi kesalahan saat memproses peminjaman.'];
    }
}

// Petugas REJECT: stok tidak berubah (memang belum pernah dikurangi).
function peminjaman_reject(mysqli $conn, int $id, int $adminId): bool {
    return db_run($conn,
        "UPDATE peminjaman SET status = 'rejected', approved_by = ?, approved_at = ? WHERE id = ? AND status = 'pending'",
        'isi', [$adminId, date('Y-m-d H:i:s'), $id]) > 0;
}

function peminjaman_hitung_pending(mysqli $conn): int {
    return (int)db_one($conn, "SELECT COUNT(*) AS n FROM peminjaman WHERE status = 'pending'")['n'];
}

// Jumlah buku yang sedang dipinjam mahasiswa (approved & pengembalian belum disetujui).
function peminjaman_aktif_hitung(mysqli $conn, int $userId): int {
    $r = db_one($conn,
        "SELECT COUNT(*) AS n FROM peminjaman p
         LEFT JOIN pengembalian pg ON pg.peminjaman_id = p.id
         WHERE p.user_id = ? AND p.status = 'approved' AND (pg.id IS NULL OR pg.status <> 'approved')",
        'i', [$userId]);
    return (int)$r['n'];
}

// ===== Keterlambatan =====

// Kondisi SQL "terlambat" (butuh JOIN pengembalian pg; satu placeholder ? = tanggal hari ini).
// Terlambat = disetujui, punya jatuh tempo, dan belum dikembalikan (atau pengembalian ditolak)
// serta lewat jatuh tempo; atau pengembalian menunggu yang DIAJUKAN setelah jatuh tempo.
function peminjaman_sql_terlambat(): string {
    return "(p.status = 'approved' AND p.tanggal_jatuh_tempo IS NOT NULL AND (
                ((pg.id IS NULL OR pg.status = 'rejected') AND p.tanggal_jatuh_tempo < ?)
                OR (pg.status = 'pending' AND pg.tanggal_kembali > p.tanggal_jatuh_tempo)))";
}

// Jumlah peminjaman terlambat (semua, atau milik satu mahasiswa).
function peminjaman_hitung_terlambat(mysqli $conn, ?int $userId = null): int {
    $sql = "SELECT COUNT(*) AS n FROM peminjaman p LEFT JOIN pengembalian pg ON pg.peminjaman_id = p.id
            WHERE " . peminjaman_sql_terlambat();
    if ($userId !== null) {
        return (int)db_one($conn, $sql . " AND p.user_id = ?", 'si', [date('Y-m-d'), $userId])['n'];
    }
    return (int)db_one($conn, $sql, 's', [date('Y-m-d')])['n'];
}

// Info keterlambatan satu baris peminjaman (butuh kolom dari peminjaman_milik / peminjaman_semua).
// 'final' = pengembalian sudah disetujui (denda sudah dibekukan); 'aktif' = buku belum selesai dikembalikan.
function peminjaman_terlambat_info(array $r): array {
    $nol = ['hari' => 0, 'denda' => 0, 'final' => false, 'aktif' => false];
    if (($r['status'] ?? '') !== 'approved' || empty($r['tanggal_jatuh_tempo'])) return $nol;
    $sk = $r['status_kembali'] ?? null;
    if ($sk === 'approved') {
        return ['hari' => (int)$r['hari_terlambat'], 'denda' => (int)$r['denda'], 'final' => true, 'aktif' => false];
    }
    // Menunggu persetujuan: denda dihitung sampai tanggal PENGAJUAN (menunggu petugas tidak menambah denda).
    $hari = hari_terlambat($r['tanggal_jatuh_tempo'], $sk === 'pending' ? $r['tanggal_kembali'] : null);
    return ['hari' => $hari, 'denda' => hitung_denda($hari), 'final' => false, 'aktif' => true];
}

// Teks/badge keterangan jatuh tempo untuk tabel.
function peminjaman_keterangan(array $r): string {
    if (($r['status'] ?? '') !== 'approved' || empty($r['tanggal_jatuh_tempo'])) return '-';
    $i = peminjaman_terlambat_info($r);
    if ($i['final']) {
        return $i['denda'] > 0
            ? '<span class="badge badge-rejected">Denda ' . rupiah($i['denda']) . ' (' . $i['hari'] . ' hari)</span>'
            : '<span class="badge badge-approved">Tepat waktu</span>';
    }
    if ($i['hari'] > 0) {
        return '<span class="badge badge-rejected">Terlambat ' . $i['hari'] . ' hari &middot; ' . rupiah($i['denda']) . '</span>';
    }
    if (($r['status_kembali'] ?? null) === 'pending') return 'Menunggu persetujuan pengembalian';
    $sisa = sisa_hari($r['tanggal_jatuh_tempo']);
    return $sisa <= 0 ? '<span class="badge badge-pending">Jatuh tempo hari ini</span>' : 'Sisa ' . $sisa . ' hari';
}
