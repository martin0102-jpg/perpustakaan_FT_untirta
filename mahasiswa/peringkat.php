<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/kunjungan.php';

$role = 'mahasiswa';
$judul = 'Peringkat Kunjungan';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/peringkat_view.php';
require __DIR__ . '/../includes/footer.php';
