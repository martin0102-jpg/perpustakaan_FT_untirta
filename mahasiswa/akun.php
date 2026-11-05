<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('mahasiswa');
require_once __DIR__ . '/../functions/users.php';

$role = 'mahasiswa';
$judul = 'Akun Saya';
require __DIR__ . '/../includes/akun_view.php';
