<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_role('admin');
require_once __DIR__ . '/../functions/users.php';

$role = 'admin';
$judul = 'Akun Saya';
require __DIR__ . '/../includes/akun_view.php';
