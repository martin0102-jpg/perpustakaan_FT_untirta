<?php
// Beranda: user yang sudah login diarahkan ke dashboard; tamu melihat halaman depan.
require_once __DIR__ . '/includes/auth.php';
$u = user_login();
if ($u) {
    redirect($u['role'] . '/dashboard.php');
}
require_once __DIR__ . '/functions/buku.php';
$jumlah = buku_hitung($conn);
$terbaru = buku_terbaru($conn, 4);

$judul = 'Beranda';
$penuh = true;
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-inner">
        <div class="hero-teks">
            <span class="chip">Perpustakaan Fakultas Teknik</span>
            <h1>Selamat datang di <span>Perpus FT Untirta</span></h1>
            <p>Cari buku, isi kunjungan, pinjam, dan kembalikan tanpa antre. Semua dalam satu portal.</p>
            <div class="hero-aksi">
                <a class="btn btn-besar" href="login.php">Masuk</a>
                <a class="btn btn-besar btn-garis" href="register.php">Daftar Akun</a>
            </div>
        </div>
        <div class="hero-gambar" aria-hidden="true">
            <svg viewBox="0 0 400 320" fill="none">
                <ellipse cx="200" cy="292" rx="150" ry="16" fill="#000" opacity=".18"/>
                <rect x="60" y="214" width="280" height="54" rx="8" fill="#FFC72C"/><rect x="60" y="214" width="280" height="10" rx="5" fill="#E6AE12"/>
                <rect x="84" y="150" width="236" height="54" rx="8" fill="#fff"/><rect x="84" y="150" width="236" height="10" rx="5" fill="#DDE2EA"/>
                <rect x="48" y="90" width="260" height="50" rx="8" fill="#134880"/><rect x="48" y="90" width="260" height="10" rx="5" fill="#0B2E59"/>
                <rect x="96" y="30" width="200" height="50" rx="8" fill="#FFC72C"/><rect x="96" y="30" width="200" height="10" rx="5" fill="#E6AE12"/>
                <rect x="120" y="236" width="90" height="8" rx="4" fill="#0B2E59" opacity=".35"/>
                <rect x="140" y="172" width="120" height="8" rx="4" fill="#0B2E59" opacity=".25"/>
                <circle cx="340" cy="60" r="26" fill="#FFC72C" opacity=".25"/><circle cx="52" cy="40" r="12" fill="#fff" opacity=".2"/>
            </svg>
        </div>
    </div>
</section>

<section class="seksi reveal" id="fitur">
    <div class="seksi-kepala"><span class="label">Fitur</span><h2>Mau ngapain hari ini?</h2><p>Semua kebutuhan perpustakaan ada di sini.</p></div>
    <div class="grid-3">
        <a class="fitur" href="login.php"><span class="ikon">📚</span><h3>Katalog Buku</h3><p>Lihat daftar buku beserta stok tersedia sebelum datang ke perpustakaan.</p></a>
        <a class="fitur" href="login.php"><span class="ikon">📝</span><h3>Isi Kunjungan</h3><p>Catat kunjungan Anda secara online. NIM dan tanggal terisi otomatis.</p></a>
        <a class="fitur" href="login.php"><span class="ikon">🔄</span><h3>Pinjam &amp; Kembalikan</h3><p>Ajukan peminjaman dan pengembalian, lalu pantau statusnya kapan saja.</p></a>
    </div>
</section>

<section class="seksi reveal" id="alur">
    <div class="seksi-kepala"><span class="label">Alur</span><h2>Cara menggunakan</h2><p>Hanya empat langkah.</p></div>
    <div class="grid-4">
        <div class="langkah"><div class="no">1</div><h3>Daftar &amp; Masuk</h3><p>Buat akun dengan NIM, lalu login.</p></div>
        <div class="langkah"><div class="no">2</div><h3>Isi Kunjungan</h3><p>Petugas akan memverifikasi kunjungan Anda.</p></div>
        <div class="langkah"><div class="no">3</div><h3>Ajukan Pinjam</h3><p>Pilih buku, tunggu persetujuan petugas.</p></div>
        <div class="langkah"><div class="no">4</div><h3>Kembalikan</h3><p>Ajukan pengembalian setelah selesai membaca.</p></div>
    </div>
</section>

<?php if ($terbaru): ?>
<section class="seksi reveal">
    <div class="seksi-kepala"><span class="label">Koleksi</span><h2>Buku terbaru</h2><p><?= (int)$jumlah ?> judul buku terdaftar.</p></div>
    <div class="grid-4">
    <?php foreach ($terbaru as $b): ?>
        <div class="buku-card">
            <div class="sampul">
                <?php if ($url = buku_gambar_url($b['gambar'] ?? null)): ?><img src="<?= e($url) ?>" alt="Cover <?= e($b['judul']) ?>" loading="lazy">
                <?php else: ?><?= e(teks_awal($b['judul'])) ?><?php endif; ?>
            </div>
            <div class="isi">
                <h3><?= e($b['judul']) ?></h3>
                <p class="penulis"><?= e($b['penulis']) ?> &middot; <?= e($b['tahun']) ?></p>
                <?= (int)$b['stok'] > 0 ? '<span class="badge badge-approved">Stok ' . (int)$b['stok'] . '</span>' : '<span class="badge badge-rejected">Habis</span>' ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="cta reveal">
    <div class="cta-isi">
        <div><h2>Siap meminjam buku?</h2><p>Buat akun gratis dan mulai dalam hitungan menit.</p></div>
        <a class="btn btn-besar" href="register.php">Daftar Sekarang</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
