<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin');
require_once __DIR__ . '/../functions/buku.php';

$errors = [];
$form = ['judul' => '', 'penulis' => '', 'penerbit' => '', 'tahun' => '', 'isbn' => '', 'stok' => '0'];
$editId = 0;
$gambarSaatIni = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus') {
        // Aturan: buku yang pernah dipinjam tidak boleh dihapus (menjaga riwayat).
        if (buku_hapus($conn, $id)) {
            set_flash('sukses', 'Buku berhasil dihapus.');
        } else {
            set_flash('error', 'Buku tidak dapat dihapus karena sudah punya riwayat peminjaman.');
        }
        redirect('admin/buku.php');
    }

    if ($aksi === 'simpan') {
        [$form, $errors] = buku_validasi($_POST);
        $lama = null;
        if ($id > 0) {
            $b = buku_cari_id($conn, $id);
            if ($b) { $lama = $b['gambar'] ?? null; } else { $errors[] = 'Buku tidak ditemukan.'; }
        }
        $gambarSaatIni = $lama;

        // Proses file cover (opsional) hanya jika data lain valid.
        $baru = null;
        if (!$errors) {
            [$ok, $hasil] = buku_simpan_gambar($_FILES['gambar'] ?? []);
            if ($ok) { $baru = $hasil; } else { $errors[] = $hasil; }
        }

        if (!$errors) {
            // Tentukan nama gambar akhir: upload baru > hapus gambar > tetap yang lama.
            $gambar = $baru ?? (!empty($_POST['hapus_gambar']) ? null : $lama);
            $form['gambar'] = $gambar;
            try {
                if ($id > 0) { buku_ubah($conn, $id, $form); set_flash('sukses', 'Data buku diperbarui.'); }
                else         { buku_tambah($conn, $form);    set_flash('sukses', 'Buku baru ditambahkan.'); }
                if ($gambar !== $lama) buku_hapus_gambar($lama); // buang file lama yang sudah tak dipakai
                redirect('admin/buku.php');
            } catch (mysqli_sql_exception $e) {
                buku_hapus_gambar($baru); // batalkan file yang baru terlanjur tersimpan
                error_log('Simpan buku gagal: ' . $e->getMessage());
                $errors[] = 'Data gagal disimpan. Pastikan migrasi database (kolom gambar) sudah dijalankan.';
            }
        }
        $editId = $id; // tampilkan ulang form beserta error
    }
} elseif (isset($_GET['edit'])) {
    // Mode edit: isi form dengan data buku yang dipilih.
    $b = buku_cari_id($conn, (int)$_GET['edit']);
    if ($b) { $form = $b; $editId = (int)$b['id']; $gambarSaatIni = $b['gambar'] ?? null; }
}

$cari = trim($_GET['cari'] ?? '');
$daftar = buku_semua($conn, $cari);

$judul = 'Kelola Buku';
require __DIR__ . '/../includes/header.php';
?>
<h1>Kelola Buku</h1>
<p class="sub">Tambah, ubah, hapus buku, atur stok, dan unggah cover.</p>

<div class="card">
    <h2><?= $editId ? 'Ubah buku' : 'Tambah buku' ?></h2>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" value="<?= $editId ?>">
        <div class="form-grid">
            <div><label for="judul">Judul</label><input type="text" id="judul" name="judul" value="<?= e($form['judul']) ?>" required></div>
            <div><label for="penulis">Penulis</label><input type="text" id="penulis" name="penulis" value="<?= e($form['penulis']) ?>" required></div>
            <div><label for="penerbit">Penerbit</label><input type="text" id="penerbit" name="penerbit" value="<?= e($form['penerbit']) ?>"></div>
            <div><label for="tahun">Tahun terbit</label><input type="number" id="tahun" name="tahun" value="<?= e($form['tahun']) ?>" required></div>
            <div><label for="isbn">ISBN</label><input type="text" id="isbn" name="isbn" value="<?= e($form['isbn']) ?>" maxlength="20"></div>
            <div><label for="stok">Stok</label><input type="number" id="stok" name="stok" min="0" value="<?= e($form['stok']) ?>" required></div>
        </div>
        <div class="gambar-input">
            <?php if ($gambarSaatIni): ?>
                <img class="preview-gambar" src="<?= e(buku_gambar_url($gambarSaatIni)) ?>" alt="Cover saat ini">
            <?php endif; ?>
            <div>
                <label for="gambar">Cover buku <small>(JPG/PNG/WebP, maks. 2 MB, opsional)</small></label>
                <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
                <?php if ($gambarSaatIni): ?>
                    <label class="cek"><input type="checkbox" name="hapus_gambar" value="1"> Hapus cover saat ini</label>
                <?php endif; ?>
            </div>
        </div>
        <button class="btn" type="submit"><?= $editId ? 'Simpan perubahan' : 'Tambah buku' ?></button>
        <?php if ($editId): ?><a class="btn btn-sekunder" href="buku.php">Batal</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h2>Daftar buku</h2>
    <form class="cari" method="get">
        <input type="search" name="cari" placeholder="Cari judul, penulis, atau ISBN" value="<?= e($cari) ?>">
        <button class="btn btn-sekunder" type="submit">Cari</button>
    </form>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Cover</th><th>Judul</th><th>Penulis</th><th>Penerbit</th><th>Tahun</th><th>ISBN</th><th>Stok</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (!$daftar): ?><tr><td colspan="8" class="kosong">Belum ada buku.</td></tr><?php endif; ?>
        <?php foreach ($daftar as $b): ?>
            <tr>
                <td><?php if (!empty($b['gambar'])): ?><img class="thumb" src="<?= e(buku_gambar_url($b['gambar'])) ?>" alt="" loading="lazy"><?php else: ?><span class="thumb thumb-kosong"><?= e(teks_awal($b['judul'])) ?></span><?php endif; ?></td>
                <td><?= e($b['judul']) ?></td><td><?= e($b['penulis']) ?></td><td><?= e($b['penerbit']) ?></td>
                <td><?= e($b['tahun']) ?></td><td><?= e($b['isbn']) ?></td><td><?= e($b['stok']) ?></td>
                <td class="aksi">
                    <a class="btn btn-kecil btn-sekunder" href="?edit=<?= (int)$b['id'] ?>">Ubah</a>
                    <form class="inline" method="post" data-konfirmasi="Hapus buku ini?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <button class="btn btn-kecil btn-bahaya" type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
