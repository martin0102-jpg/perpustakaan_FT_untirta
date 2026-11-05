<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/pengembalian.php';

$uid = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    [$ok, $pesan] = pengembalian_ajukan($conn, $uid, (int)($_POST['peminjaman_id'] ?? 0));
    set_flash($ok ? 'sukses' : 'error', $pesan);
    redirect('mahasiswa/pengembalian.php');
}

$bisa = pengembalian_bisa_diajukan($conn, $uid);
$riwayat = pengembalian_riwayat($conn, $uid);

$judul = 'Pengembalian';
require __DIR__ . '/../includes/header.php';
?>
<h1>Pengembalian</h1>
<p class="sub">Stok bertambah setelah petugas menyetujui pengembalian. Denda dihitung sampai tanggal Anda mengajukan.</p>
<div class="card">
    <h2>Buku yang dapat dikembalikan</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tgl pinjam</th><th>Buku</th><th>Jatuh tempo</th><th>Keterangan</th><th></th></tr></thead>
        <tbody>
        <?php if (!$bisa): ?><tr><td colspan="5" class="kosong">Tidak ada buku yang perlu dikembalikan.</td></tr><?php endif; ?>
        <?php foreach ($bisa as $p): $hari = hari_terlambat($p['tanggal_jatuh_tempo'] ?? null); ?>
            <tr>
                <td><?= tgl($p['tanggal_pinjam']) ?></td><td><?= e($p['judul']) ?></td>
                <td><?= tgl($p['tanggal_jatuh_tempo'] ?? null) ?></td>
                <td>
                    <?php if ($hari > 0): ?><span class="badge badge-rejected">Terlambat <?= $hari ?> hari &middot; perkiraan denda <?= e(rupiah(hitung_denda($hari))) ?></span>
                    <?php elseif ($p['status_kembali'] === 'rejected'): ?>Pengembalian sebelumnya ditolak, silakan ajukan lagi.
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td class="aksi">
                    <form class="inline" method="post" data-konfirmasi="Ajukan pengembalian buku ini?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="peminjaman_id" value="<?= (int)$p['id'] ?>">
                        <button class="btn btn-kecil" type="submit">Ajukan pengembalian</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<div class="card">
    <h2>Riwayat pengembalian</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tgl pinjam</th><th>Tgl kembali</th><th>Buku</th><th>Status</th><th>Denda</th></tr></thead>
        <tbody>
        <?php if (!$riwayat): ?><tr><td colspan="5" class="kosong">Belum ada pengajuan pengembalian.</td></tr><?php endif; ?>
        <?php foreach ($riwayat as $r): ?>
            <tr><td><?= tgl($r['tanggal_pinjam']) ?></td><td><?= tgl($r['tanggal_kembali']) ?></td>
                <td><?= e($r['judul']) ?></td><td><?= badge($r['status']) ?></td>
                <td><?= $r['status'] === 'approved' && (int)$r['denda'] > 0 ? '<strong>' . e(rupiah((int)$r['denda'])) . '</strong> (' . (int)$r['hari_terlambat'] . ' hari)' : '-' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
