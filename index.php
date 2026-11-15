<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$judul = 'Dashboard';

$hari = date('Y-m-d');
$totalBarang = (int)$pdo->query('SELECT COUNT(*) FROM barang')->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM barang_masuk WHERE tanggal = ?');
$st->execute([$hari]);
$masukHariIni = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM barang_keluar WHERE tanggal = ?');
$st->execute([$hari]);
$keluarHariIni = (int)$st->fetchColumn();

$menipis = $pdo->query(
    'SELECT b.*, k.nama_kategori FROM barang b JOIN kategori k ON k.id_kategori = b.id_kategori
     WHERE b.stok < b.stok_minimum ORDER BY b.kode_barang'
)->fetchAll();

$aktivitas = $pdo->query(
    "(SELECT 'Masuk' AS jenis, m.tanggal, m.id_masuk AS id, b.nama_barang, m.jumlah, b.satuan
        FROM barang_masuk m JOIN barang b ON b.kode_barang = m.kode_barang)
     UNION ALL
     (SELECT 'Keluar', k.tanggal, k.id_keluar, b.nama_barang, k.jumlah, b.satuan
        FROM barang_keluar k JOIN barang b ON b.kode_barang = k.kode_barang)
     ORDER BY tanggal DESC, id DESC LIMIT 8"
)->fetchAll();

$habis = 0;
foreach ($menipis as $b) { if ((float)$b['stok'] <= 0) { $habis++; } }
$jam = (int)date('G');
$salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));

include __DIR__ . '/includes/header.php';
?>
<div class="welcome">
    <div>
        <h2><?= e($salam) ?>, <?= e(user()['nama']) ?></h2>
        <p>Ringkasan stok <?= e(APP_NAME) ?> hari ini.</p>
    </div>
    <div class="quick">
        <a class="btn" href="barang_masuk.php"><?= icon('down', 16) ?> Barang Masuk</a>
        <a class="btn" href="barang_keluar.php"><?= icon('up', 16) ?> Barang Keluar</a>
        <a class="btn" href="stok_opname.php"><?= icon('clipboard', 16) ?> Stok Opname</a>
    </div>
</div>

<div class="stats">
    <div class="card"><span class="stat-icon"><?= icon('package', 24) ?></span><div class="stat-body"><span>Jenis Barang</span><b><?= $totalBarang ?></b></div></div>
    <div class="card stat-brown"><span class="stat-icon"><?= icon('down', 24) ?></span><div class="stat-body"><span>Transaksi Masuk Hari Ini</span><b><?= $masukHariIni ?></b></div></div>
    <div class="card stat-brown"><span class="stat-icon"><?= icon('up', 24) ?></span><div class="stat-body"><span>Transaksi Keluar Hari Ini</span><b><?= $keluarHariIni ?></b></div></div>
    <div class="card stat-warn"><span class="stat-icon"><?= icon('alert', 24) ?></span><div class="stat-body"><span>Stok Menipis</span><b><?= count($menipis) - $habis ?></b></div></div>
    <div class="card stat-danger"><span class="stat-icon"><?= icon('xcircle', 24) ?></span><div class="stat-body"><span>Stok Habis</span><b><?= $habis ?></b></div></div>
</div>

<div class="card">
    <h3 class="card-title">Barang di Bawah Stok Minimum</h3>
    <?php if (!$menipis): ?>
        <div class="msg ok"><?= icon('check', 18) ?><span>Semua stok aman. Tidak ada barang di bawah batas minimum.</span></div>
    <?php else: ?>
    <div class="wrap"><table>
        <tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Satuan</th><th>Stok</th><th>Minimum</th><th>Status</th></tr>
        <?php foreach ($menipis as $b): ?>
            <tr class="low"><td><?= e($b['kode_barang']) ?></td><td><?= e($b['nama_barang']) ?></td>
            <td><?= e($b['nama_kategori']) ?></td><td><?= e($b['satuan']) ?></td>
            <td class="s"><?= qty($b['stok']) ?></td><td><?= qty($b['stok_minimum']) ?></td><td><?= badge_stok($b) ?></td></tr>
        <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 class="card-title">Aktivitas Terakhir</h3>
    <?php if (!$aktivitas): ?>
        <p class="sub">Belum ada transaksi.</p>
    <?php else: ?>
    <div class="wrap"><table>
        <tr><th>Tanggal</th><th>Jenis</th><th>Barang</th><th>Jumlah</th></tr>
        <?php foreach ($aktivitas as $a): ?>
            <tr><td><?= tgl($a['tanggal']) ?></td><td><span class="badge badge-<?= $a['jenis'] === 'Masuk' ? 'masuk' : 'keluar' ?>"><?= e($a['jenis']) ?></span></td>
            <td><?= e($a['nama_barang']) ?></td><td><?= qty($a['jumlah']) ?> <?= e($a['satuan']) ?></td></tr>
        <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
