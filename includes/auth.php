<?php
// ===== Autentikasi, session, dan helper umum =====
// Di-require oleh setiap halaman (juga memuat koneksi database).
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/aturan.php';

// Cookie session: tidak bisa dibaca JavaScript (httponly), dan tidak ikut dikirim dari situs lain (samesite).
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true,
        'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// Escape output agar aman dari XSS.
function e($teks): string {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

// Data user yang sedang login (atau null).
function user_login(): ?array {
    return $_SESSION['user'] ?? null;
}

// Halaman wajib login.
function wajib_login(): void {
    if (!user_login()) {
        redirect('login.php');
    }
    // Akun dengan password sementara (hasil reset petugas) wajib menggantinya dulu.
    if (!empty($_SESSION['user']['wajib_ganti']) && basename($_SERVER['SCRIPT_NAME']) !== 'akun.php') {
        set_flash('error', 'Ganti password sementara Anda terlebih dahulu.');
        redirect($_SESSION['user']['role'] . '/akun.php');
    }
}

// Halaman wajib login DAN punya role tertentu ('admin' / 'mahasiswa').
// Jika role salah, user diarahkan ke dashboard miliknya sendiri.
function wajib_role(string $role): void {
    wajib_login();
    if ($_SESSION['user']['role'] !== $role) {
        redirect($_SESSION['user']['role'] . '/dashboard.php');
    }
}

// ----- CSRF: mencegah form disubmit dari situs lain -----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_cek(): void {
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(400);
        exit('Permintaan tidak valid (token CSRF salah). Muat ulang halaman.');
    }
}

// ----- Flash message: pesan sekali tampil setelah redirect -----
function set_flash(string $tipe, string $pesan): void {
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan]; // tipe: sukses / error
}
function ambil_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ----- Helper teks (tidak butuh ekstensi mbstring, jadi aman di hosting mana pun) -----
function teks_panjang(string $s): int {
    return (int)preg_match_all('/./us', $s);
}
function teks_awal(string $s): string {
    return preg_match('/./us', $s, $m) ? strtoupper($m[0]) : '?';
}

// ----- Helper tampilan -----
function tgl(?string $tanggal): string {
    return $tanggal ? date('d/m/Y', strtotime($tanggal)) : '-';
}
function tgl_jam(?string $waktu): string {
    return $waktu ? date('d/m/Y H:i', strtotime($waktu)) : '-';
}
function badge(string $status): string {
    $label = ['pending' => 'Menunggu', 'verified' => 'Terverifikasi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    return '<span class="badge badge-' . e($status) . '">' . e($label[$status] ?? $status) . '</span>';
}
// Tautan filter status: Semua | Menunggu | ...
function filter_status(string $aktif, array $opsi): void {
    echo '<div class="filter">';
    echo '<a href="?" class="' . ($aktif === '' ? 'aktif' : '') . '">Semua</a>';
    foreach ($opsi as $nilai => $label) {
        echo '<a href="?status=' . e($nilai) . '" class="' . ($aktif === $nilai ? 'aktif' : '') . '">' . e($label) . '</a>';
    }
    echo '</div>';
}
// Ambil filter status dari URL, hanya nilai yang diizinkan.
function status_filter(array $diizinkan): string {
    $s = $_GET['status'] ?? '';
    return in_array($s, $diizinkan, true) ? $s : '';
}

// ----- Pembatas percobaan login: 5 kali salah -> tunggu 60 detik -----
function login_sisa_kunci(): int {
    $sampai = $_SESSION['login_kunci'] ?? 0;
    return $sampai > time() ? $sampai - time() : 0;
}
function login_gagal(): void {
    $_SESSION['login_gagal'] = ($_SESSION['login_gagal'] ?? 0) + 1;
    if ($_SESSION['login_gagal'] >= 5) {
        $_SESSION['login_kunci'] = time() + 60;
        $_SESSION['login_gagal'] = 0;
    }
}

// ----- Helper bulan (untuk peringkat) & privasi NIM -----
function nama_bulan(int $m): string {
    $n = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $n[$m] ?? '';
}
// Validasi format 'YYYY-MM'; mengembalikan null jika tidak valid.
function bulan_valid(string $b): ?string {
    if (!preg_match('/^(\d{4})-(\d{2})$/', $b, $m)) return null;
    $th = (int)$m[1]; $bl = (int)$m[2];
    return ($th >= 2000 && $th <= 2100 && $bl >= 1 && $bl <= 12) ? $b : null;
}
function label_bulan(string $b): string {
    [$th, $bl] = explode('-', $b);
    return nama_bulan((int)$bl) . ' ' . $th;
}
// Menyamarkan NIM untuk tampilan ke sesama mahasiswa: 3337****01
function samarkan_nim(string $nim): string {
    $n = strlen($nim);
    return $n <= 6 ? str_repeat('*', $n) : substr($nim, 0, 4) . str_repeat('*', $n - 6) . substr($nim, -2);
}

// ----- Helper denda & jatuh tempo -----
function rupiah(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}
// Jumlah hari terlambat: selisih $sampai (default hari ini) dengan jatuh tempo; minimal 0.
function hari_terlambat(?string $jatuhTempo, ?string $sampai = null): int {
    if (!$jatuhTempo) return 0;
    $selisih = (strtotime($sampai ?? date('Y-m-d')) - strtotime($jatuhTempo)) / 86400;
    return max(0, (int)round($selisih));
}
function hitung_denda(int $hari): int {
    return $hari * DENDA_PER_HARI;
}
// Sisa hari menuju jatuh tempo (negatif jika sudah lewat).
function sisa_hari(string $jatuhTempo): int {
    return (int)round((strtotime($jatuhTempo) - strtotime(date('Y-m-d'))) / 86400);
}
