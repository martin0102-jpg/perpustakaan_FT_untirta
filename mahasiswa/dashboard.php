<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/peminjaman.php';
require_once __DIR__ . '/../functions/kunjungan.php';

$uid = $_SESSION['user']['id'];
$aktif = peminjaman_aktif_hitung($conn, $uid);
$pending = peminjaman_punya_pending($conn, $uid);

// Buku yang terlambat + total perkiraan denda.
$telat = []; $totalDenda = 0;
foreach (peminjaman_milik($conn, $uid) as $r) {
    $i = peminjaman_terlambat_info($r);
    if ($i['aktif'] && $i['hari'] > 0) { $telat[] = $r + ['hari_telat' => $i['hari']]; $totalDenda += $i['denda']; }
}

// Peringkat kunjungan saya bulan ini (null jika belum punya kunjungan terverifikasi).
$peringkatSaya = null;
foreach (kunjungan_peringkat($conn, date('Y-m')) as $r) {
    if ((int)$r['id'] === $uid) $peringkatSaya = $r;
}

$judul = 'Dashboard Mahasiswa';
require __DIR__ . '/../includes/header.php';
?>
<div class="banner"><h1>Halo, <?= e($_SESSION['user']['nama']) ?> 👋</h1><p>NIM <?= e($_SESSION['user']['username']) ?></p></div>
<?php if ($telat): ?>
<div class="alert alert-error" style="animation:none">
    <strong>⚠️ <?= count($telat) ?> buku terlambat dikembalikan.</strong> Perkiraan denda saat ini <?= e(rupiah($totalDenda)) ?>.
    Anda tidak dapat meminjam lagi sebelum mengembalikannya.
    <a href="pengembalian.php">Ajukan pengembalian</a>
    <ul><?php foreach ($telat as $t): ?><li><?= e($t['judul']) ?> &mdash; terlambat <?= (int)$t['hari_telat'] ?> hari</li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<div class="stat-grid">
    <a class="stat" href="pengembalian.php"><span class="ikon">📖</span><span><span class="angka"><?= $aktif ?></span><span class="label">Buku sedang dipinjam</span></span></a>
    <a class="stat" href="peminjaman.php"><span class="ikon">🕘</span><span><span class="angka"><?= $pending ? 1 : 0 ?></span><span class="label">Pengajuan pinjam menunggu</span></span></a>
    <a class="stat" href="peminjaman.php"><span class="ikon">⏰</span><span><span class="angka"><?= count($telat) ?></span><span class="label">Buku terlambat</span></span></a>
    <a class="stat" href="peringkat.php"><span class="ikon">🏆</span><span><span class="angka"><?= $peringkatSaya ? (int)$peringkatSaya['peringkat'] : '-' ?></span><span class="label"><?= $peringkatSaya ? 'Peringkat kunjungan bulan ini' : 'Belum ada kunjungan terverifikasi bulan ini' ?></span></span></a>
</div>
<div class="card">
    <h2>Menu cepat</h2>
    <a class="btn" href="kunjungan.php">Isi kunjungan</a>
    <a class="btn btn-sekunder" href="buku.php">Lihat buku</a>
    <a class="btn btn-sekunder" href="peminjaman.php">Ajukan peminjaman</a>
    <a class="btn btn-sekunder" href="pengembalian.php">Ajukan pengembalian</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
