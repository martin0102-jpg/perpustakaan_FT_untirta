<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin'); // hanya admin
require_once __DIR__ . '/../functions/buku.php';
require_once __DIR__ . '/../functions/kunjungan.php';
require_once __DIR__ . '/../functions/peminjaman.php';
require_once __DIR__ . '/../functions/pengembalian.php';

$judul = 'Dashboard Petugas';
require __DIR__ . '/../includes/header.php';
?>
<div class="banner"><h1>Dashboard Petugas</h1><p>Ringkasan pekerjaan yang menunggu tindakan Anda.</p></div>
<div class="stat-grid">
    <a class="stat" href="kunjungan.php?status=pending"><span class="ikon">📝</span><span><span class="angka"><?= kunjungan_hitung_pending($conn) ?></span><span class="label">Kunjungan menunggu verifikasi</span></span></a>
    <a class="stat" href="peminjaman.php?status=pending"><span class="ikon">📥</span><span><span class="angka"><?= peminjaman_hitung_pending($conn) ?></span><span class="label">Peminjaman menunggu persetujuan</span></span></a>
    <a class="stat" href="pengembalian.php?status=pending"><span class="ikon">📤</span><span><span class="angka"><?= pengembalian_hitung_pending($conn) ?></span><span class="label">Pengembalian menunggu persetujuan</span></span></a>
    <a class="stat" href="buku.php"><span class="ikon">📚</span><span><span class="angka"><?= buku_hitung($conn) ?></span><span class="label">Judul buku terdaftar</span></span></a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
