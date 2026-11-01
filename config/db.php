<?php
// ===== Konfigurasi & koneksi database =====
// Ubah nilai di bawah ini sesuai lingkungan (XAMPP lokal / InfinityFree).

// BASE_URL: awalan URL project. XAMPP lokal: '/perpustakaan'.
// InfinityFree (di folder htdocs langsung): '' (kosong).
define('BASE_URL', '/perpustakaan_FT_untirta');

$DB_HOST = 'sql306.infinityfree.com';          // InfinityFree: sqlXXX.infinityfree.com
$DB_USER = 'if0_43321194';               // InfinityFree: if0_XXXXXXXX
$DB_PASS = 'HsQLLJSyZ1kMi';                   // InfinityFree: password akun hosting
$DB_NAME = 'if0_43321194_perpus';   // InfinityFree: if0_XXXXXXXX_perpus_ft_untirta (sesuai panel)

date_default_timezone_set('Asia/Jakarta');

// Mode strict: error SQL menjadi exception, sehingga bisa ditangkap try/catch
// (penting untuk transaksi: kalau gagal, kita bisa rollback).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa config/db.php.'); // detail error tidak ditampilkan ke user
}

// ===== Helper query: SEMUA query memakai mysqli_prepare =====

// Menjalankan prepared statement, mengembalikan objek statement.
function db_stmt(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

// SELECT: mengembalikan semua baris sebagai array asosiatif.
function db_all(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $stmt = db_stmt($conn, $sql, $types, $params);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

// SELECT: hanya satu baris (atau null jika tidak ada).
function db_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array {
    $rows = db_all($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

// INSERT/UPDATE/DELETE: mengembalikan jumlah baris yang terpengaruh.
function db_run(mysqli $conn, string $sql, string $types = '', array $params = []): int {
    $stmt = db_stmt($conn, $sql, $types, $params);
    $n = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $n;
}
