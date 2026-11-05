<?php
// ===== Header + navbar sticky =====
// Halaman pemanggil mengisi $judul; set $penuh = true untuk layout lebar penuh (landing).
$u = user_login();
$judul = $judul ?? 'Perpus FT Untirta';
$penuh = $penuh ?? false;
$hal = basename($_SERVER['SCRIPT_NAME']);
$flash = ambil_flash();
$ver = @filemtime(__DIR__ . '/../assets/css/style.css') ?: 1; // cache-busting: browser otomatis memuat CSS/JS terbaru

if ($u) {
    $prefix = BASE_URL . '/' . $u['role'] . '/';
    $menu = [
        $prefix . 'dashboard.php'    => ['Dashboard', 'dashboard.php'],
        $prefix . 'buku.php'         => ['Buku', 'buku.php'],
    ];
    if ($u['role'] === 'admin') {
        $menu[$prefix . 'mahasiswa.php'] = ['Mahasiswa', 'mahasiswa.php']; // kelola akun & reset password
    }
    $menu += [
        $prefix . 'kunjungan.php'    => ['Kunjungan', 'kunjungan.php'],
        $prefix . 'peminjaman.php'   => ['Peminjaman', 'peminjaman.php'],
        $prefix . 'pengembalian.php' => ['Pengembalian', 'pengembalian.php'],
        $prefix . 'peringkat.php'    => ['Peringkat', 'peringkat.php'],
    ];
} else {
    $menu = [
        BASE_URL . '/index.php'         => ['Beranda', 'index.php'],
        BASE_URL . '/index.php#fitur'   => ['Fitur', ''],
        BASE_URL . '/index.php#alur'    => ['Alur', ''],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B2E59">
    <title><?= e($judul) ?> - Perpus FT Untirta</title>
    <script>document.documentElement.classList.add('js');</script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= $ver ?>">
</head>
<body>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="<?= BASE_URL ?>/index.php">
            <svg width="32" height="32" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#FFC72C"/><path d="M7 9h6.5a3 3 0 0 1 2.5 1.2A3 3 0 0 1 18.5 9H25v14h-6.5a3 3 0 0 0-2.5 1.2A3 3 0 0 0 13.5 23H7z" fill="#0B2E59"/><path d="M16 10.5v13" stroke="#FFC72C" stroke-width="1.5"/></svg>
            <span>Perpus FT Untirta</span>
        </a>
        <nav class="nav" id="menu" aria-label="Menu utama">
            <?php foreach ($menu as $url => [$label, $file]): ?>
                <a href="<?= e($url) ?>" class="<?= $file !== '' && $hal === $file ? 'aktif' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="nav-kanan">
            <?php if ($u): ?>
            <div class="user">
                <button class="user-btn" id="user-btn" aria-expanded="false" aria-haspopup="true">
                    <span class="avatar"><?= e(teks_awal($u['nama'])) ?></span>
                    <span class="user-nama"><?= e($u['nama']) ?></span>
                </button>
                <div class="dropdown" id="user-menu">
                    <div class="info"><strong><?= e($u['nama']) ?></strong><?= e($u['username']) ?> &middot; <?= e($u['role']) ?></div>
                    <a href="<?= BASE_URL . '/' . e($u['role']) ?>/dashboard.php">Dashboard</a>
                    <a href="<?= BASE_URL . '/' . e($u['role']) ?>/akun.php">Akun &amp; ganti password</a>
                    <a href="<?= BASE_URL ?>/logout.php">Keluar</a>
                </div>
            </div>
            <?php else: ?>
                <a class="btn btn-kecil btn-garis" href="<?= BASE_URL ?>/login.php">Masuk</a>
                <a class="btn btn-kecil" href="<?= BASE_URL ?>/register.php">Daftar</a>
            <?php endif; ?>
            <button class="burger" id="burger" aria-label="Buka menu" aria-expanded="false" aria-controls="menu">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
</header>
<main class="<?= $penuh ? 'main-penuh' : 'container' ?>">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>
