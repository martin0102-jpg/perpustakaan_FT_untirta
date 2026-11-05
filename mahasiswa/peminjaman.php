<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/buku.php';
require_once __DIR__ . '/../functions/peminjaman.php';

$uid = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    [$ok, $pesan] = peminjaman_ajukan($conn, $uid, (int)($_POST['buku_id'] ?? 0));
    set_flash($ok ? 'sukses' : 'error', $pesan);
    redirect('mahasiswa/peminjaman.php');
}

$bukuTersedia = buku_tersedia($conn);
$punyaPending = peminjaman_punya_pending($conn, $uid);
$riwayat = peminjaman_milik($conn, $uid);
$dipilih = (int)($_GET['buku'] ?? 0); // dari tombol "Pinjam" di halaman buku

$judul = 'Peminjaman';
require __DIR__ . '/../includes/header.php';
?>
<h1>Peminjaman</h1>
<p class="sub">Batas pinjam <?= LAMA_PINJAM_HARI ?> hari sejak disetujui. Denda <?= rupiah(DENDA_PER_HARI) ?> per hari keterlambatan.</p>
<div class="card">
    <h2>Ajukan peminjaman</h2>
    <?php if ($punyaPending): ?>
        <div class="alert alert-error">Anda masih punya pengajuan yang menunggu persetujuan. Pengajuan baru bisa dibuat setelah diproses petugas.</div>
    <?php elseif (!$bukuTersedia): ?>
        <p class="kosong">Semua buku sedang habis.</p>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div>
                <label for="buku_id">Pilih buku</label>
                <select id="buku_id" name="buku_id" required>
                    <option value="">-- Pilih buku --</option>
                    <?php foreach ($bukuTersedia as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= $dipilih === (int)$b['id'] ? 'selected' : '' ?>>
                            <?= e($b['judul']) ?> - <?= e($b['penulis']) ?> (stok <?= (int)$b['stok'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button class="btn" type="submit">Ajukan peminjaman</button>
    </form>
    <?php endif; ?>
</div>
<div class="card">
    <h2>Riwayat peminjaman</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Tgl pinjam</th><th>Buku</th><th>Jatuh tempo</th><th>Status pinjam</th><th>Pengembalian</th><th>Keterangan</th></tr></thead>
        <tbody>
        <?php if (!$riwayat): ?><tr><td colspan="6" class="kosong">Belum ada peminjaman.</td></tr><?php endif; ?>
        <?php foreach ($riwayat as $p): ?>
            <tr><td><?= tgl($p['tanggal_pinjam']) ?></td><td><?= e($p['judul']) ?></td><td><?= tgl($p['tanggal_jatuh_tempo'] ?? null) ?></td><td><?= badge($p['status']) ?></td>
                <td><?= $p['status_kembali'] ? badge($p['status_kembali']) : '-' ?></td><td><?= peminjaman_keterangan($p) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
