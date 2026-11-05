<?php
// ===== Fungsi query: AKUN (ganti & reset password) =====

// Validasi password baru. Mengembalikan pesan error atau null jika valid.
function password_baru_error(string $baru, string $konfirmasi): ?string {
    if (strlen($baru) < 6)  return 'Password baru minimal 6 karakter.';
    if (strlen($baru) > 72) return 'Password baru maksimal 72 karakter.'; // batas bcrypt
    if ($baru !== $konfirmasi) return 'Konfirmasi password baru tidak sama.';
    return null;
}

// Ganti password sendiri: wajib memasukkan password lama.
function user_ganti_password(mysqli $conn, int $id, string $lama, string $baru, string $konfirmasi): array {
    $user = db_one($conn, "SELECT password FROM users WHERE id = ?", 'i', [$id]);
    if (!$user || !password_verify($lama, $user['password'])) {
        return [false, 'Password lama salah.'];
    }
    if ($err = password_baru_error($baru, $konfirmasi)) {
        return [false, $err];
    }
    if ($baru === $lama) {
        return [false, 'Password baru tidak boleh sama dengan password lama.'];
    }
    db_run($conn, "UPDATE users SET password = ?, wajib_ganti_password = 0 WHERE id = ?",
        'si', [password_hash($baru, PASSWORD_DEFAULT), $id]);
    return [true, 'Password berhasil diganti.'];
}

// Password sementara acak (tanpa karakter yang mudah tertukar: 0/O, 1/l/I).
function password_sementara(int $panjang = 8): string {
    $huruf = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $hasil = '';
    for ($i = 0; $i < $panjang; $i++) {
        $hasil .= $huruf[random_int(0, strlen($huruf) - 1)];
    }
    return $hasil;
}

// Daftar akun mahasiswa untuk admin (pencarian opsional).
function user_mahasiswa_semua(mysqli $conn, string $cari = ''): array {
    $sql = "SELECT id, nama, username, wajib_ganti_password FROM users WHERE role = 'mahasiswa'";
    if ($cari === '') {
        return db_all($conn, $sql . " ORDER BY nama");
    }
    $like = '%' . $cari . '%';
    return db_all($conn, $sql . " AND (nama LIKE ? OR username LIKE ?) ORDER BY nama", 'ss', [$like, $like]);
}

// Reset password mahasiswa oleh petugas. Hanya role 'mahasiswa' yang bisa direset lewat sini.
// Mengembalikan [nama, nim, password_sementara] atau null jika gagal.
function user_reset_password(mysqli $conn, int $id): ?array {
    $u = db_one($conn, "SELECT nama, username FROM users WHERE id = ? AND role = 'mahasiswa'", 'i', [$id]);
    if (!$u) return null;
    $sementara = password_sementara();
    db_run($conn, "UPDATE users SET password = ?, wajib_ganti_password = 1 WHERE id = ? AND role = 'mahasiswa'",
        'si', [password_hash($sementara, PASSWORD_DEFAULT), $id]);
    return [$u['nama'], $u['username'], $sementara];
}
