<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/buku.php';

$cari = trim($_GET['cari'] ?? '');
$daftar = buku_semua($conn, $cari); // read-only: mahasiswa hanya melihat

$judul = 'Daftar Buku';
require __DIR__ . '/../includes/header.php';
?>
<h1>Daftar Buku</h1>
<p class="sub">Cek ketersediaan stok sebelum meminjam.</p>
<div class="card">
    <form class="cari" method="get">
        <input type="search" name="cari" placeholder="Cari judul, penulis, atau ISBN" value="<?= e($cari) ?>">
        <button class="btn btn-sekunder" type="submit">Cari</button>
    </form>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Judul</th><th>Penulis</th><th>Penerbit</th><th>Tahun</th><th>ISBN</th><th>Stok</th><th></th></tr></thead>
        <tbody>
        <?php if (!$daftar): ?><tr><td colspan="7" class="kosong">Buku tidak ditemukan.</td></tr><?php endif; ?>
        <?php foreach ($daftar as $b): ?>
            <tr>
                <td><?= e($b['judul']) ?></td><td><?= e($b['penulis']) ?></td><td><?= e($b['penerbit']) ?></td>
                <td><?= e($b['tahun']) ?></td><td><?= e($b['isbn']) ?></td>
                <td><?= (int)$b['stok'] > 0 ? e($b['stok']) : '<span class="badge badge-rejected">Habis</span>' ?></td>
                <td class="aksi"><?php if ((int)$b['stok'] > 0): ?><a class="btn btn-kecil" href="peminjaman.php?buku=<?= (int)$b['id'] ?>">Pinjam</a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
