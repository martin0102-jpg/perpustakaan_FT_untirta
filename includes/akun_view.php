<?php
// ===== Halaman akun: info + ganti password (dipakai admin & mahasiswa) =====
// Variabel $role wajib diisi oleh halaman pemanggil. Tidak ada $conn di sini selain dari auth.php.
$errors = [];
$wajib = !empty($_SESSION['user']['wajib_ganti']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    [$ok, $pesan] = user_ganti_password($conn, $_SESSION['user']['id'],
        $_POST['lama'] ?? '', $_POST['baru'] ?? '', $_POST['konfirmasi'] ?? '');
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user']['wajib_ganti'] = 0;
        set_flash('sukses', $pesan);
        redirect($role . '/dashboard.php');
    }
    $errors[] = $pesan; // password tidak pernah ditampilkan kembali
}

require __DIR__ . '/header.php';
?>
<h1>Akun Saya</h1>
<p class="sub">Kelola keamanan akun Anda.</p>

<?php if ($wajib): ?>
    <div class="alert alert-penting">Anda masuk memakai password sementara dari petugas. Buat password baru sekarang untuk melanjutkan.</div>
<?php endif; ?>

<div class="card">
    <h2>Informasi akun</h2>
    <p style="margin:0"><strong><?= e($_SESSION['user']['nama']) ?></strong><br>
    <?= $role === 'admin' ? 'Username' : 'NIM' ?>: <?= e($_SESSION['user']['username']) ?> &middot; Peran: <?= e($role) ?></p>
</div>

<div class="card" style="max-width:520px">
    <h2>Ganti password</h2>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field" style="margin-bottom:16px"><label for="lama"><?= $wajib ? 'Password sementara' : 'Password lama' ?></label>
            <input type="password" id="lama" name="lama" required autocomplete="current-password"></div>
        <div class="field" style="margin-bottom:16px"><label for="baru">Password baru (minimal 6 karakter)</label>
            <input type="password" id="baru" name="baru" required autocomplete="new-password"></div>
        <div class="field" style="margin-bottom:16px"><label for="konfirmasi">Konfirmasi password baru</label>
            <input type="password" id="konfirmasi" name="konfirmasi" required autocomplete="new-password"></div>
        <button class="btn" type="submit">Simpan password</button>
    </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
