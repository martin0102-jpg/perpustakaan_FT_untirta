<?php
// ===== Autentikasi, session, dan helper umum =====
// Di-require oleh setiap halaman (juga memuat koneksi database).
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
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
