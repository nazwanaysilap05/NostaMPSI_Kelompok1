<?php
// Variabel yang dibutuhkan: $judul
$u = user();
$file = basename($_SERVER['PHP_SELF']);
$menu = [
    'Menu Utama' => [
        'index.php' => ['Dashboard', 'grid'], 'barang.php' => ['Data Barang', 'package'],
    ],
    'Transaksi' => [
        'barang_masuk.php' => ['Barang Masuk', 'down'], 'barang_keluar.php' => ['Barang Keluar', 'up'],
        'stok_opname.php' => ['Stok Opname', 'clipboard'],
    ],
];
if (is_admin()) {
    $menu['Administrasi'] = [
        'laporan.php' => ['Laporan', 'file'], 'kategori.php' => ['Kategori', 'tag'],
        'pemasok.php' => ['Pemasok', 'truck'], 'pengguna.php' => ['Pengguna', 'users'],
    ];
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$inisial = mb_strtoupper(mb_substr($u['nama'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> - <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="assets/favicon.png">
<link rel="shortcut icon" href="assets/favicon.ico">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
<meta name="theme-color" content="#4b5d25">
<link rel="stylesheet" href="style.css">
</head>
<body class="page-<?= e(basename($file, '.php')) ?>">
<div class="app">
<aside class="sidebar noprint" id="sidebar" aria-label="Menu utama">
    <div class="brand">
        <img src="assets/logo-light.png" alt="Logo Nosta" class="brand-logo" width="44" height="44">
        <div class="brand-text"><strong>NOSTA COFFEE</strong><small>Heritage House</small></div>
    </div>
    <nav class="menu">
        <?php foreach ($menu as $navGrup => $navItems): ?>
            <div class="menu-group"><?= e($navGrup) ?></div>
            <?php foreach ($navItems as $navHref => $navItem): ?>
                <a href="<?= e($navHref) ?>" class="<?= $navHref === $file ? 'on' : '' ?>"<?= $navHref === $file ? ' aria-current="page"' : '' ?>><?= icon($navItem[1], 18) ?><span><?= e($navItem[0]) ?></span></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <form method="post" action="logout.php" class="userbox">
        <?= csrf_field() ?>
        <div class="user-line">
            <span class="avatar"><?= e($inisial) ?></span>
            <span class="user-info"><strong><?= e($u['nama']) ?></strong><small><?= e(ucfirst($u['peran'])) ?></small></span>
        </div>
        <button class="logout" type="submit"><?= icon('logout', 16) ?> Logout</button>
    </form>
</aside>
<div class="overlay noprint" id="overlay"></div>
<div class="content">
<header class="topbar noprint">
    <button type="button" class="burger" id="burger" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false"><?= icon('menu', 22) ?></button>
    <img src="assets/logo-light.png" alt="" class="topbar-logo" width="28" height="28">
    <strong class="topbar-title">Nosta Coffee</strong>
    <span class="avatar sm"><?= e($inisial) ?></span>
</header>
<main>
<div class="print-kop">
    <img src="assets/logo.png" alt="Logo Nosta" width="64" height="64">
    <div>
        <div class="kop-nama"><?= e(APP_NAME) ?></div>
        <div class="kop-sub"><?= e(APP_SUB) ?></div>
    </div>
</div>
<div class="page-head noprint-flex">
    <h1><?= e($judul) ?></h1>
    <span class="page-date noprint"><?= e(tanggal_panjang()) ?></span>
</div>
<?php if ($flash): ?>
    <div class="msg <?= $flash['type'] === 'err' ? 'err' : 'ok' ?>" role="alert"><?= icon($flash['type'] === 'err' ? 'alert' : 'check', 18) ?><span><?= e($flash['msg']) ?></span></div>
<?php endif; ?>
