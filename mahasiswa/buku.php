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
    <?php if (!$daftar): ?><p class="kosong">Buku tidak ditemukan.</p><?php endif; ?>
    <div class="grid-4 katalog">
    <?php foreach ($daftar as $b): $url = buku_gambar_url($b['gambar'] ?? null); ?>
        <div class="buku-card">
            <div class="sampul">
                <?php if ($url): ?><img src="<?= e($url) ?>" alt="Cover <?= e($b['judul']) ?>" loading="lazy">
                <?php else: ?><?= e(teks_awal($b['judul'])) ?><?php endif; ?>
            </div>
            <div class="isi">
                <h3><?= e($b['judul']) ?></h3>
                <p class="penulis"><?= e($b['penulis']) ?> &middot; <?= e($b['tahun']) ?></p>
                <?php if ((int)$b['stok'] > 0): ?>
                    <span class="badge badge-approved">Stok <?= (int)$b['stok'] ?></span>
                    <a class="btn btn-kecil" href="peminjaman.php?buku=<?= (int)$b['id'] ?>">Pinjam</a>
                <?php else: ?>
                    <span class="badge badge-rejected">Habis</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
