<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin');
require_once __DIR__ . '/../functions/users.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $hasil = user_reset_password($conn, (int)($_POST['id'] ?? 0));
    if ($hasil) {
        [$nama, $nim, $sementara] = $hasil;
        // Password sementara hanya tampil SEKALI di pesan ini (tidak disimpan dalam bentuk asli).
        set_flash('penting', "Password sementara untuk $nama ($nim): $sementara — berikan kepada yang bersangkutan. Ia wajib menggantinya saat masuk.");
    } else {
        set_flash('error', 'Akun mahasiswa tidak ditemukan.');
    }
    redirect('admin/mahasiswa.php');
}

$cari = trim($_GET['cari'] ?? '');
$daftar = user_mahasiswa_semua($conn, $cari);

$judul = 'Kelola Mahasiswa';
require __DIR__ . '/../includes/header.php';
?>
<h1>Kelola Mahasiswa</h1>
<p class="sub">Reset password bagi mahasiswa yang lupa. Password sementara hanya tampil sekali.</p>
<div class="card">
    <form class="cari" method="get">
        <input type="search" name="cari" placeholder="Cari nama atau NIM" value="<?= e($cari) ?>">
        <button class="btn btn-sekunder" type="submit">Cari</button>
    </form>
    <div class="table-wrap">
    <table>
        <thead><tr><th>NIM</th><th>Nama</th><th>Status password</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (!$daftar): ?><tr><td colspan="4" class="kosong">Belum ada akun mahasiswa.</td></tr><?php endif; ?>
        <?php foreach ($daftar as $m): ?>
            <tr>
                <td><?= e($m['username']) ?></td><td><?= e($m['nama']) ?></td>
                <td><?= $m['wajib_ganti_password'] ? '<span class="badge badge-pending">Menunggu diganti</span>' : '-' ?></td>
                <td class="aksi">
                    <form class="inline" method="post" data-konfirmasi="Reset password <?= e($m['nama']) ?>? Password lama tidak bisa dipakai lagi.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button class="btn btn-kecil" type="submit">Reset password</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
