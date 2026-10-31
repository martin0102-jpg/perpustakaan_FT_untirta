<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/kunjungan.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    // user_id dari SESSION (bukan dari form) sehingga tidak bisa dipalsukan.
    [$ok, $pesan] = kunjungan_tambah($conn, $_SESSION['user']['id']);
    set_flash($ok ? 'sukses' : 'error', $pesan);
    redirect('mahasiswa/kunjungan.php');
}
$riwayat = kunjungan_milik($conn, $_SESSION['user']['id']);

$judul = 'Kunjungan';
require __DIR__ . '/../includes/header.php';
?>
<h1>Kunjungan</h1>
<p class="sub">NIM, tanggal, dan jam terisi otomatis.</p>
<div class="card">
    <p>Mengisi sebagai <strong><?= e($_SESSION['user']['nama']) ?></strong> (<?= e($_SESSION['user']['username']) ?>) pada <strong><?= date('d/m/Y') ?></strong>.</p>
    <form method="post"><?= csrf_field() ?><button class="btn" type="submit">Isi kunjungan hari ini</button></form>
</div>
<div class="card">
    <h2>Riwayat kunjungan</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tanggal</th><th>Jam</th><th>Status</th><th>Diverifikasi oleh</th></tr></thead>
        <tbody>
        <?php if (!$riwayat): ?><tr><td colspan="4" class="kosong">Belum ada kunjungan. Isi kunjungan pertama Anda di atas.</td></tr><?php endif; ?>
        <?php foreach ($riwayat as $k): ?>
            <tr><td><?= tgl($k['tanggal']) ?></td><td><?= e(substr($k['jam_submit'], 0, 5)) ?></td>
                <td><?= badge($k['status']) ?></td><td><?= e($k['nama_petugas'] ?? '-') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
