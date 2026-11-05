<?php
// ===== Tampilan peringkat kunjungan =====
// Dipakai admin/peringkat.php dan mahasiswa/peringkat.php. Variabel $role wajib diisi.
$bulan  = bulan_valid($_GET['bulan'] ?? '') ?? date('Y-m');
$semua  = kunjungan_peringkat($conn, $bulan);
$top    = array_slice($semua, 0, 10);
$juara  = $semua[0] ?? null;
$medali = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
$idSaya = (int)$_SESSION['user']['id'];
$saya   = null;
foreach ($semua as $r) { if ((int)$r['id'] === $idSaya) $saya = $r; }

// Pilihan bulan: 12 bulan terakhir.
$opsi = [];
for ($i = 0; $i < 12; $i++) { $opsi[] = date('Y-m', strtotime('first day of -' . $i . ' month')); }
if (!in_array($bulan, $opsi, true)) array_unshift($opsi, $bulan);
$tampilNim = fn(string $n) => $role === 'admin' ? $n : samarkan_nim($n);
?>
<div class="banner">
    <h1>🏆 Peringkat Kunjungan</h1>
    <p>Pengunjung paling rajin bulan <?= e(label_bulan($bulan)) ?></p>
</div>

<?php if ($juara): ?>
<div class="juara">
    <span class="juara-ikon">🥇</span>
    <div>
        <small>Pengunjung Terbaik <?= e(label_bulan($bulan)) ?></small>
        <strong><?= e($juara['nama']) ?></strong>
        <span><?= e($tampilNim($juara['username'])) ?> &middot; <?= (int)$juara['jumlah'] ?> kunjungan</span>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'mahasiswa'): ?>
<div class="alert <?= $saya ? 'alert-sukses' : 'alert-error' ?>" style="animation:none">
    <?php if ($saya): ?>Peringkat Anda bulan ini: <strong>#<?= (int)$saya['peringkat'] ?></strong> dari <?= count($semua) ?> pengunjung (<?= (int)$saya['jumlah'] ?> kunjungan).
    <?php else: ?>Anda belum punya kunjungan terverifikasi di bulan ini.<?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <form class="cari" method="get">
        <select name="bulan" data-auto-submit aria-label="Pilih bulan">
            <?php foreach ($opsi as $o): ?>
                <option value="<?= e($o) ?>" <?= $o === $bulan ? 'selected' : '' ?>><?= e(label_bulan($o)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sekunder" type="submit">Tampilkan</button>
    </form>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Peringkat</th><th>Nama</th><th>NIM</th><th>Kunjungan</th></tr></thead>
        <tbody>
        <?php if (!$top): ?><tr><td colspan="4" class="kosong">Belum ada kunjungan terverifikasi di bulan ini.</td></tr><?php endif; ?>
        <?php foreach ($top as $r): ?>
            <tr class="<?= (int)$r['id'] === $idSaya && $role === 'mahasiswa' ? 'saya' : '' ?>">
                <td><strong><?= $medali[$r['peringkat']] ?? '#' . (int)$r['peringkat'] ?></strong></td>
                <td><?= e($r['nama']) ?></td>
                <td><?= e($tampilNim($r['username'])) ?></td>
                <td><?= (int)$r['jumlah'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p class="sub" style="margin:14px 0 0;font-size:.88rem">
        Hanya kunjungan berstatus <strong>Terverifikasi</strong> yang dihitung. Jika jumlahnya sama, yang lebih dulu mencapai jumlah tersebut berada di atas.
    </p>
</div>
