<?php
// ===== Fungsi query: KUNJUNGAN =====

// Mahasiswa mengisi kunjungan. Tanggal & jam diambil otomatis dari server.
// Aturan tambahan: satu kunjungan per hari agar tidak terjadi spam.
function kunjungan_tambah(mysqli $conn, int $userId): array {
    $hariIni = date('Y-m-d');
    $sudah = db_one($conn, "SELECT id FROM kunjungan WHERE user_id = ? AND tanggal = ?", 'is', [$userId, $hariIni]);
    if ($sudah) {
        return [false, 'Anda sudah mengisi kunjungan hari ini.'];
    }
    try {
        db_run($conn,
            "INSERT INTO kunjungan (user_id, tanggal, jam_submit, status) VALUES (?, ?, ?, 'pending')",
            'iss', [$userId, $hariIni, date('H:i:s')]);
    } catch (mysqli_sql_exception $e) {
        // UNIQUE (user_id, tanggal) menolak klik ganda bersamaan.
        return [false, 'Anda sudah mengisi kunjungan hari ini.'];
    }
    return [true, 'Kunjungan berhasil dikirim, menunggu verifikasi petugas.'];
}

function kunjungan_milik(mysqli $conn, int $userId): array {
    return db_all($conn,
        "SELECT k.*, v.nama AS nama_petugas
         FROM kunjungan k LEFT JOIN users v ON v.id = k.verified_by
         WHERE k.user_id = ? ORDER BY k.id DESC", 'i', [$userId]);
}

// Semua kunjungan (admin), dengan filter status opsional.
function kunjungan_semua(mysqli $conn, string $status = ''): array {
    $sql = "SELECT k.*, u.nama, u.username AS nim, v.nama AS nama_petugas
            FROM kunjungan k
            JOIN users u ON u.id = k.user_id
            LEFT JOIN users v ON v.id = k.verified_by";
    if ($status !== '') {
        return db_all($conn, $sql . " WHERE k.status = ? ORDER BY k.id DESC", 's', [$status]);
    }
    return db_all($conn, $sql . " ORDER BY k.id DESC");
}

// Petugas memverifikasi: $status = 'verified' atau 'rejected'.
// Klausa "AND status = 'pending'" mencegah satu kunjungan diproses dua kali.
function kunjungan_proses(mysqli $conn, int $id, string $status, int $adminId): bool {
    if (!in_array($status, ['verified', 'rejected'], true)) return false;
    return db_run($conn,
        "UPDATE kunjungan SET status = ?, verified_by = ?, verified_at = ? WHERE id = ? AND status = 'pending'",
        'sisi', [$status, $adminId, date('Y-m-d H:i:s'), $id]) > 0;
}

function kunjungan_hitung_pending(mysqli $conn): int {
    return (int)db_one($conn, "SELECT COUNT(*) AS n FROM kunjungan WHERE status = 'pending'")['n'];
}

// ===== Peringkat kunjungan per bulan =====
// Hanya kunjungan berstatus 'verified' yang dihitung (anti-curang: harus diverifikasi petugas).
// Jika jumlah sama, yang LEBIH DULU mencapai jumlah itu (kunjungan terakhirnya lebih awal) menang.
// $bulan berformat 'YYYY-MM'. Mengembalikan semua baris berperingkat 1..n.
function kunjungan_peringkat(mysqli $conn, string $bulan): array {
    $awal  = $bulan . '-01';
    $akhir = date('Y-m-d', strtotime($awal . ' +1 month')); // batas atas (tidak ikut)
    $baris = db_all($conn,
        "SELECT u.id, u.nama, u.username, COUNT(*) AS jumlah,
                MAX(CONCAT(k.tanggal, ' ', k.jam_submit)) AS terakhir
         FROM kunjungan k JOIN users u ON u.id = k.user_id
         WHERE k.status = 'verified' AND k.tanggal >= ? AND k.tanggal < ?
         GROUP BY u.id, u.nama, u.username
         ORDER BY jumlah DESC, terakhir ASC",
        'ss', [$awal, $akhir]);
    foreach ($baris as $i => &$b) {
        $b['peringkat'] = $i + 1;
    }
    return $baris;
}
