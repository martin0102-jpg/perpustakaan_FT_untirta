<?php
// ===== Halaman login (admin: username, mahasiswa: NIM) =====
require_once __DIR__ . '/includes/auth.php';

if (user_login()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cari user lewat prepared statement (aman dari SQL injection).
    $user = db_one($conn, "SELECT id, nama, username, password, role FROM users WHERE username = ?", 's', [$username]);

    // Pesan error sengaja dibuat sama agar tidak membocorkan apakah username terdaftar.
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // cegah session fixation
        $_SESSION['user'] = [
            'id' => (int)$user['id'], 'nama' => $user['nama'],
            'username' => $user['username'], 'role' => $user['role'],
        ];
        redirect($user['role'] . '/dashboard.php');
    }
    $error = 'NIM/username atau password salah.';
}

$judul = 'Masuk';
require __DIR__ . '/includes/header.php';
?>
<div class="login-box card">
    <h1>Masuk</h1>
    <p class="sub">Gunakan NIM (mahasiswa) atau username (petugas).</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label for="username">NIM / Username</label>
            <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button class="btn" type="submit">Masuk</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
