<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin'); // hanya admin
require_once __DIR__ . '/../functions/buku.php';
require_once __DIR__ . '/../functions/kunjungan.php';
require_once __DIR__ . '/../functions/peminjaman.php';
require_once __DIR__ . '/../functions/pengembalian.php';

$juaraBulanIni = kunjungan_peringkat($conn, date('Y-m'))[0] ?? null;

$judul = 'Dashboard Petugas';
require __DIR__ . '/../includes/header.php';
?>
<div class="banner"><h1>Dashboard Petugas</h1><p>Ringkasan pekerjaan yang menunggu tindakan Anda.</p></div>
<div class="stat-grid">
    <a class="stat" href="kunjungan.php?status=pending"><span class="ikon">📝</span><span><span class="angka"><?= kunjungan_hitung_pending($conn) ?></span><span class="label">Kunjungan menunggu verifikasi</span></span></a>
    <a class="stat" href="peminjaman.php?status=pending"><span class="ikon">📥</span><span><span class="angka"><?= peminjaman_hitung_pending($conn) ?></span><span class="label">Peminjaman menunggu persetujuan</span></span></a>
    <a class="stat" href="pengembalian.php?status=pending"><span class="ikon">📤</span><span><span class="angka"><?= pengembalian_hitung_pending($conn) ?></span><span class="label">Pengembalian menunggu persetujuan</span></span></a>
    <a class="stat" href="peminjaman.php?status=terlambat"><span class="ikon">⏰</span><span><span class="angka"><?= peminjaman_hitung_terlambat($conn) ?></span><span class="label">Peminjaman terlambat</span></span></a>
    <a class="stat" href="buku.php"><span class="ikon">📚</span><span><span class="angka"><?= buku_hitung($conn) ?></span><span class="label">Judul buku terdaftar</span></span></a>
</div>
<div class="card">
    <h2>🏆 Pengunjung terbaik bulan <?= e(label_bulan(date('Y-m'))) ?></h2>
    <?php if ($juaraBulanIni): ?>
        <p style="margin:0"><strong><?= e($juaraBulanIni['nama']) ?></strong> (<?= e($juaraBulanIni['username']) ?>) &middot; <?= (int)$juaraBulanIni['jumlah'] ?> kunjungan terverifikasi.
        <a href="peringkat.php">Lihat peringkat lengkap</a></p>
    <?php else: ?>
        <p class="sub" style="margin:0">Belum ada kunjungan terverifikasi bulan ini. <a href="kunjungan.php?status=pending">Verifikasi kunjungan</a></p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
