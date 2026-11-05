<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin');
require_once __DIR__ . '/../functions/pengembalian.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)($_POST['id'] ?? 0);
    $adminId = $_SESSION['user']['id'];
    if (($_POST['aksi'] ?? '') === 'approve') {
        [$ok, $pesan] = pengembalian_approve($conn, $id, $adminId); // stok bertambah di sini
        set_flash($ok ? 'sukses' : 'error', $pesan);
    } else {
        $ok = pengembalian_reject($conn, $id, $adminId);
        set_flash($ok ? 'sukses' : 'error', $ok ? 'Pengembalian ditolak.' : 'Pengembalian tidak ditemukan atau sudah diproses.');
    }
    redirect('admin/pengembalian.php' . (!empty($_POST['kembali']) ? '?status=' . urlencode($_POST['kembali']) : ''));
}

$filter = status_filter(['pending', 'approved', 'rejected']);
$daftar = pengembalian_semua($conn, $filter);

$judul = 'Persetujuan Pengembalian';
require __DIR__ . '/../includes/header.php';
?>
<h1>Persetujuan Pengembalian</h1>
<p class="sub">Stok buku bertambah saat Anda menyetujui pengembalian.</p>
<div class="card">
    <?php filter_status($filter, ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak']); ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tgl pinjam</th><th>Tgl kembali</th><th>NIM</th><th>Nama</th><th>Buku</th><th>Jatuh tempo</th><th>Terlambat</th><th>Denda</th><th>Status</th><th>Diproses oleh</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (!$daftar): ?><tr><td colspan="11" class="kosong">Tidak ada data pengembalian.</td></tr><?php endif; ?>
        <?php foreach ($daftar as $r):
            // Disetujui: pakai nilai yang sudah dibekukan. Menunggu: perkiraan sampai tanggal pengajuan.
            $hari  = $r['status'] === 'approved' ? (int)$r['hari_terlambat'] : hari_terlambat($r['tanggal_jatuh_tempo'], $r['tanggal_kembali']);
            $denda = $r['status'] === 'approved' ? (int)$r['denda'] : hitung_denda($hari);
            $tampil = $r['status'] !== 'rejected'; ?>
            <tr>
                <td><?= tgl($r['tanggal_pinjam']) ?></td><td><?= tgl($r['tanggal_kembali']) ?></td>
                <td><?= e($r['nim']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['judul']) ?></td>
                <td><?= tgl($r['tanggal_jatuh_tempo'] ?? null) ?></td>
                <td><?= $tampil ? ($hari > 0 ? '<span class="badge badge-rejected">' . $hari . ' hari</span>' : 'Tepat waktu') : '-' ?></td>
                <td><?= $tampil && $denda > 0 ? '<strong>' . e(rupiah($denda)) . '</strong>' : '-' ?></td>
                <td><?= badge($r['status']) ?></td><td><?= e($r['nama_petugas'] ?? '-') ?></td>
                <td class="aksi">
                <?php if ($r['status'] === 'pending'): ?>
                    <?php foreach (['approve' => ['Setujui', ''], 'reject' => ['Tolak', ' btn-bahaya']] as $aksi => [$label, $kelas]): ?>
                    <form class="inline" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
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
