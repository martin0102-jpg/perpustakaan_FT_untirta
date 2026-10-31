<?php
// Pintu masuk: arahkan ke dashboard sesuai role, atau ke halaman login.
require_once __DIR__ . '/includes/auth.php';
$u = user_login();
redirect($u ? $u['role'] . '/dashboard.php' : 'login.php');
