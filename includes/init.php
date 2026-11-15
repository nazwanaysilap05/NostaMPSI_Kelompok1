<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/icons.php';

date_default_timezone_set('Asia/Jakarta');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Koneksi database gagal. Pastikan MySQL menyala, database sudah diimpor (database.sql), dan config.php benar.');
}

// Buat akun awal jika tabel pengguna masih kosong
try {
    if ((int)$pdo->query('SELECT COUNT(*) FROM pengguna')->fetchColumn() === 0) {
        $st = $pdo->prepare('INSERT INTO pengguna (nama, username, password, peran) VALUES (?,?,?,?)');
        $st->execute(['Administrator', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        $st->execute(['Karyawan 1', 'karyawan', password_hash('karyawan123', PASSWORD_DEFAULT), 'karyawan']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Tabel belum ada. Impor file database.sql lewat phpMyAdmin terlebih dahulu.');
}

// ---------- fungsi bantu ----------
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }
function qty($n) { return rtrim(rtrim(number_format((float)$n, 2, ',', '.'), '0'), ','); }
function tgl($d) { return $d ? date('d/m/Y', strtotime($d)) : '-'; }
function valid_date($d) {
    $x = DateTime::createFromFormat('Y-m-d', (string)$d);
    return $x && $x->format('Y-m-d') === $d;
}
function flash($msg, $type = 'ok') { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; }
function redirect($url) { header('Location: ' . $url); exit; }
function user() { return $_SESSION['user'] ?? null; }
function is_admin() { return user() && user()['peran'] === 'admin'; }
function is_low($b) { return (float)$b['stok'] < (float)$b['stok_minimum']; }

// ---------- bantu tampilan (tidak memengaruhi logika bisnis) ----------
function badge_stok($b) {
    if ((float)$b['stok'] <= 0)  { return '<span class="badge badge-habis">Habis</span>'; }
    if (is_low($b))              { return '<span class="badge badge-menipis">Menipis</span>'; }
    return '<span class="badge badge-aman">Aman</span>';
}
function badge_opname($status) {
    $kelas = ['sesuai' => 'aman', 'menunggu' => 'menunggu', 'disetujui' => 'disetujui', 'ditolak' => 'habis'];
    return '<span class="badge badge-' . ($kelas[$status] ?? 'info') . '">' . e(ucfirst($status)) . '</span>';
}
function tanggal_panjang($ts = null) {
    $hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = $ts ?? time();
    return $hari[(int)date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['csrf'] ?? '';
        if ($t === '' || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
            http_response_code(400);
            die('Permintaan tidak valid. Silakan muat ulang halaman lalu coba lagi.');
        }
    }
}
csrf_check();

function require_login() {
    if (!user()) { redirect('login.php'); }
}
function require_admin() {
    require_login();
    if (!is_admin()) {
        flash('Halaman atau aksi ini hanya untuk admin.', 'err');
        redirect('index.php');
    }
}
