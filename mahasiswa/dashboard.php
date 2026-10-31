<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/peminjaman.php';

$uid = $_SESSION['user']['id'];
$aktif = peminjaman_aktif_hitung($conn, $uid);
$pending = peminjaman_punya_pending($conn, $uid);

$judul = 'Dashboard Mahasiswa';
require __DIR__ . '/../includes/header.php';
?>
<h1>Halo, <?= e($_SESSION['user']['nama']) ?></h1>
<p class="sub">NIM <?= e($_SESSION['user']['username']) ?></p>
<div class="stat-grid">
    <a class="stat" href="peminjaman.php"><span class="angka"><?= $aktif ?></span><span class="label">Buku sedang dipinjam</span></a>
    <a class="stat" href="peminjaman.php"><span class="angka"><?= $pending ? 1 : 0 ?></span><span class="label">Pengajuan pinjam menunggu persetujuan</span></a>
</div>
<div class="card">
    <h2>Menu cepat</h2>
    <a class="btn" href="kunjungan.php">Isi kunjungan</a>
    <a class="btn btn-sekunder" href="buku.php">Lihat buku</a>
    <a class="btn btn-sekunder" href="peminjaman.php">Ajukan peminjaman</a>
    <a class="btn btn-sekunder" href="pengembalian.php">Ajukan pengembalian</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
