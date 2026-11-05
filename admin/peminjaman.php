<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin');
require_once __DIR__ . '/../functions/peminjaman.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)($_POST['id'] ?? 0);
    $adminId = $_SESSION['user']['id'];
    if (($_POST['aksi'] ?? '') === 'approve') {
        [$ok, $pesan] = peminjaman_approve($conn, $id, $adminId); // stok berkurang di sini
        set_flash($ok ? 'sukses' : 'error', $pesan);
    } else {
        $ok = peminjaman_reject($conn, $id, $adminId);
        set_flash($ok ? 'sukses' : 'error', $ok ? 'Peminjaman ditolak.' : 'Peminjaman tidak ditemukan atau sudah diproses.');
    }
    redirect('admin/peminjaman.php' . (!empty($_POST['kembali']) ? '?status=' . urlencode($_POST['kembali']) : ''));
}

$filter = status_filter(['pending', 'approved', 'rejected', 'terlambat']);
$daftar = peminjaman_semua($conn, $filter);

$judul = 'Persetujuan Peminjaman';
require __DIR__ . '/../includes/header.php';
?>
<h1>Persetujuan Peminjaman</h1>
<p class="sub">Stok buku berkurang saat Anda menyetujui peminjaman. Batas pinjam <?= LAMA_PINJAM_HARI ?> hari, denda <?= rupiah(DENDA_PER_HARI) ?>/hari.</p>
<div class="card">
    <?php filter_status($filter, ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'terlambat' => 'Terlambat']); ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tgl pinjam</th><th>NIM</th><th>Nama</th><th>Buku</th><th>Stok saat ini</th><th>Jatuh tempo</th><th>Status</th><th>Keterangan</th><th>Diproses oleh</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (!$daftar): ?><tr><td colspan="10" class="kosong">Tidak ada data peminjaman.</td></tr><?php endif; ?>
        <?php foreach ($daftar as $p): ?>
            <tr>
                <td><?= tgl($p['tanggal_pinjam']) ?></td><td><?= e($p['nim']) ?></td><td><?= e($p['nama']) ?></td>
                <td><?= e($p['judul']) ?></td><td><?= e($p['stok']) ?></td><td><?= tgl($p['tanggal_jatuh_tempo'] ?? null) ?></td>
                <td><?= badge($p['status']) ?></td><td><?= peminjaman_keterangan($p) ?></td><td><?= e($p['nama_petugas'] ?? '-') ?></td>
                <td class="aksi">
                <?php if ($p['status'] === 'pending'): ?>
                    <?php foreach (['approve' => ['Setujui', ''], 'reject' => ['Tolak', ' btn-bahaya']] as $aksi => [$label, $kelas]): ?>
                    <form class="inline" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
