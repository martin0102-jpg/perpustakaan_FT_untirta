</main>
<footer class="footer">
    <div class="footer-grid">
        <div>
            <h4>Perpus FT Untirta</h4>
            <p>Portal perpustakaan Fakultas Teknik: lihat buku, isi kunjungan, pinjam, dan kembalikan dengan mudah.</p>
        </div>
        <div>
            <h4>Menu</h4>
            <ul>
            <?php if (user_login()): $p = BASE_URL . '/' . user_login()['role'] . '/'; ?>
                <li><a href="<?= $p ?>dashboard.php">Dashboard</a></li>
                <li><a href="<?= $p ?>buku.php">Buku</a></li>
                <li><a href="<?= $p ?>peminjaman.php">Peminjaman</a></li>
                <li><a href="<?= BASE_URL ?>/logout.php">Keluar</a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li><a href="<?= BASE_URL ?>/login.php">Masuk</a></li>
                <li><a href="<?= BASE_URL ?>/register.php">Daftar akun</a></li>
            <?php endif; ?>
            </ul>
        </div>
        <div>
            <h4>Informasi</h4>
            <ul><li>Project mata kuliah Sistem Basis Data</li><li>PHP Native &middot; MySQL</li></ul>
        </div>
    </div>
    <div class="footer-bawah">&copy; <?= date('Y') ?> Perpus FT Untirta</div>
</footer>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?: 1 ?>"></script>
</body>
</html>
