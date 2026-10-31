<?php
// ===== Header + navbar =====
// Halaman pemanggil mengisi variabel $judul sebelum require file ini.
$u = user_login();
$judul = $judul ?? 'Perpustakaan Kampus';
$hal = basename($_SERVER['SCRIPT_NAME']);
$menu = [
    'dashboard.php'    => 'Dashboard',
    'buku.php'         => 'Buku',
    'kunjungan.php'    => 'Kunjungan',
    'peminjaman.php'   => 'Peminjaman',
    'pengembalian.php' => 'Pengembalian',
];
$flash = ambil_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> - Perpustakaan Kampus</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="<?= BASE_URL ?>/index.php">Perpustakaan Kampus</a>
        <?php if ($u): ?>
        <nav class="nav">
            <?php foreach ($menu as $file => $label): ?>
                <a href="<?= BASE_URL . '/' . e($u['role']) . '/' . $file ?>" class="<?= $hal === $file ? 'aktif' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="user-info">
            <span><?= e($u['nama']) ?> (<?= e($u['role']) ?>)</span>
            <a href="<?= BASE_URL ?>/logout.php">Keluar</a>
        </div>
        <?php endif; ?>
    </div>
</header>
<main class="container">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>
