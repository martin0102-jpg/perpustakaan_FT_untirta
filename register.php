<?php
// ===== Registrasi mahasiswa (halaman publik) =====
require_once __DIR__ . '/includes/auth.php';

// User yang sudah login tidak perlu mendaftar lagi.
if (user_login()) {
    redirect('index.php');
}

$errors = [];
$nim = '';
$nama = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $nim       = trim($_POST['nim'] ?? '');
    $nama      = trim($_POST['nama'] ?? '');
    $password  = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    // ----- Validasi -----
    if ($nim === '' || !ctype_digit($nim)) {
        $errors[] = 'NIM wajib diisi dan hanya boleh berisi angka.';
    } elseif (strlen($nim) > 20) {
        $errors[] = 'NIM terlalu panjang (maksimal 20 digit).';
    }
    if ($nama === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    } elseif (teks_panjang($nama) > 100) {
        $errors[] = 'Nama terlalu panjang (maksimal 100 karakter).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    } elseif (strlen($password) > 72) {
        $errors[] = 'Password maksimal 72 karakter.'; // batas bcrypt: lebih dari itu dipotong diam-diam
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    // Cek NIM belum terdaftar (hanya jika format NIM sudah benar).
    if (!$errors && db_one($conn, "SELECT id FROM users WHERE username = ?", 's', [$nim])) {
        $errors[] = 'NIM sudah terdaftar. Silakan login.';
    }

    // ----- Simpan -----
    if (!$errors) {
        try {
            // Role ditulis langsung 'mahasiswa' di SQL, tidak pernah dari input form.
            db_run($conn,
                "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'mahasiswa')",
                'sss', [$nama, $nim, password_hash($password, PASSWORD_DEFAULT)]);
            redirect('login.php?daftar=sukses');
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                // 1062 = duplicate entry: NIM sudah ada (mis. dua pendaftaran bersamaan)
                $errors[] = 'NIM sudah terdaftar. Silakan login.';
            } else {
                // Error lain dicatat di log server; user hanya melihat pesan umum.
                error_log('Register gagal: ' . $e->getMessage());
                $errors[] = 'Pendaftaran gagal karena kesalahan server. Coba lagi.';
            }
        }
    }
}

$judul = 'Daftar Akun';
require __DIR__ . '/includes/header.php';
?>
<div class="login-box card">
    <h1>Daftar Akun Mahasiswa</h1>
    <p class="sub">Isi data berikut untuk membuat akun.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label for="nim">NIM</label>
            <input type="text" id="nim" name="nim" value="<?= e($nim) ?>" inputmode="numeric" maxlength="20" required autofocus>
        </div>
        <div class="field">
            <label for="nama">Nama lengkap</label>
            <input type="text" id="nama" name="nama" value="<?= e($nama) ?>" maxlength="100" required>
        </div>
        <div class="field">
            <label for="password">Password (minimal 6 karakter)</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div class="field">
            <label for="konfirmasi">Konfirmasi password</label>
            <input type="password" id="konfirmasi" name="konfirmasi" required autocomplete="new-password">
        </div>
        <button class="btn" type="submit">Daftar</button>
    </form>
    <p class="sub" style="margin:16px 0 0;text-align:center">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
