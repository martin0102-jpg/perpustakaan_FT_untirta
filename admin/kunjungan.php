<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin');
require_once __DIR__ . '/../functions/kunjungan.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $status = ($_POST['aksi'] ?? '') === 'verifikasi' ? 'verified' : 'rejected';
    if (kunjungan_proses($conn, (int)($_POST['id'] ?? 0), $status, $_SESSION['user']['id'])) {
        set_flash('sukses', $status === 'verified' ? 'Kunjungan diverifikasi.' : 'Kunjungan ditolak.');
    } else {
        set_flash('error', 'Kunjungan tidak ditemukan atau sudah diproses.');
    }
    redirect('admin/kunjungan.php' . (!empty($_POST['kembali']) ? '?status=' . urlencode($_POST['kembali']) : ''));
}

$filter = status_filter(['pending', 'verified', 'rejected']);
$daftar = kunjungan_semua($conn, $filter);

$judul = 'Verifikasi Kunjungan';
require __DIR__ . '/../includes/header.php';
?>
<h1>Verifikasi Kunjungan</h1>
<p class="sub">Periksa kunjungan mahasiswa lalu verifikasi atau tolak.</p>
<div class="card">
    <?php filter_status($filter, ['pending' => 'Menunggu', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak']); ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tanggal</th><th>Jam</th><th>NIM</th><th>Nama</th><th>Status</th><th>Diproses oleh</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (!$daftar): ?><tr><td colspan="7" class="kosong">Tidak ada data kunjungan.</td></tr><?php endif; ?>
        <?php foreach ($daftar as $k): ?>
            <tr>
                <td><?= tgl($k['tanggal']) ?></td><td><?= e(substr($k['jam_submit'], 0, 5)) ?></td>
                <td><?= e($k['nim']) ?></td><td><?= e($k['nama']) ?></td>
                <td><?= badge($k['status']) ?></td>
                <td><?= e($k['nama_petugas'] ?? '-') ?></td>
                <td class="aksi">
                <?php if ($k['status'] === 'pending'): ?>
                    <?php foreach (['verifikasi' => ['Verifikasi', ''], 'tolak' => ['Tolak', ' btn-bahaya']] as $aksi => [$label, $kelas]): ?>
                    <form class="inline" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                        <input type="hidden" name="aksi" value="<?= $aksi ?>">
                        <input type="hidden" name="kembali" value="<?= e($filter) ?>">
                        <button class="btn btn-kecil<?= $kelas ?>" type="submit"><?= $label ?></button>
                    </form>
                    <?php endforeach; ?>
                <?php else: ?>-<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
