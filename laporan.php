<?php
require_once __DIR__ . '/includes/init.php';
require_admin();
$judul = 'Laporan';

$jenisList = [
    'stok'   => 'Laporan Stok Barang',
    'masuk'  => 'Laporan Barang Masuk',
    'keluar' => 'Laporan Barang Keluar',
    'opname' => 'Laporan Stok Opname',
];
$jenis = $_GET['jenis'] ?? 'stok';
if (!isset($jenisList[$jenis])) { $jenis = 'stok'; }
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if (!valid_date($dari)) { $dari = date('Y-m-01'); }
if (!valid_date($sampai)) { $sampai = date('Y-m-d'); }
if ($dari > $sampai) { $tmp = $dari; $dari = $sampai; $sampai = $tmp; }

function agg(PDO $pdo, $sql, array $p) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    $out = [];
    foreach ($st->fetchAll() as $r) { $out[$r['k']] = (float)$r['v']; }
    return $out;
}

$rows = [];
if ($jenis === 'stok') {
    $dalam = 'tanggal BETWEEN ? AND ?';
    $masuk   = agg($pdo, "SELECT kode_barang AS k, SUM(jumlah) AS v FROM barang_masuk WHERE $dalam GROUP BY kode_barang", [$dari, $sampai]);
    $masukS  = agg($pdo, 'SELECT kode_barang AS k, SUM(jumlah) AS v FROM barang_masuk WHERE tanggal > ? GROUP BY kode_barang', [$sampai]);
    $keluar  = agg($pdo, "SELECT kode_barang AS k, SUM(jumlah) AS v FROM barang_keluar WHERE $dalam GROUP BY kode_barang", [$dari, $sampai]);
    $keluarS = agg($pdo, 'SELECT kode_barang AS k, SUM(jumlah) AS v FROM barang_keluar WHERE tanggal > ? GROUP BY kode_barang', [$sampai]);
    $adj     = agg($pdo, "SELECT kode_barang AS k, SUM(selisih) AS v FROM stok_opname WHERE status_persetujuan = 'disetujui' AND $dalam GROUP BY kode_barang", [$dari, $sampai]);
    $adjS    = agg($pdo, "SELECT kode_barang AS k, SUM(selisih) AS v FROM stok_opname WHERE status_persetujuan = 'disetujui' AND tanggal > ? GROUP BY kode_barang", [$sampai]);
    $barang = $pdo->query('SELECT b.*, k.nama_kategori FROM barang b JOIN kategori k ON k.id_kategori = b.id_kategori ORDER BY b.kode_barang')->fetchAll();
    foreach ($barang as $b) {
        $kd = $b['kode_barang'];
        $m = $masuk[$kd] ?? 0; $k = $keluar[$kd] ?? 0; $a = $adj[$kd] ?? 0;
        $akhir = (float)$b['stok'] - ($masukS[$kd] ?? 0) + ($keluarS[$kd] ?? 0) - ($adjS[$kd] ?? 0);
        $awal = $akhir - $m + $k - $a;
        $rows[] = ['b' => $b, 'awal' => $awal, 'masuk' => $m, 'keluar' => $k, 'adj' => $a, 'akhir' => $akhir];
    }
} elseif ($jenis === 'masuk') {
    $st = $pdo->prepare(
        'SELECT m.*, b.nama_barang, b.satuan, p.nama_pemasok, u.nama AS petugas FROM barang_masuk m
         JOIN barang b ON b.kode_barang = m.kode_barang
         LEFT JOIN pemasok p ON p.id_pemasok = m.id_pemasok
         JOIN pengguna u ON u.id_pengguna = m.id_pengguna
         WHERE m.tanggal BETWEEN ? AND ? ORDER BY m.tanggal, m.id_masuk'
    );
    $st->execute([$dari, $sampai]);
    $rows = $st->fetchAll();
} elseif ($jenis === 'keluar') {
    $st = $pdo->prepare(
        'SELECT k.*, b.nama_barang, b.satuan, u.nama AS petugas FROM barang_keluar k
         JOIN barang b ON b.kode_barang = k.kode_barang
         JOIN pengguna u ON u.id_pengguna = k.id_pengguna
         WHERE k.tanggal BETWEEN ? AND ? ORDER BY k.tanggal, k.id_keluar'
    );
    $st->execute([$dari, $sampai]);
    $rows = $st->fetchAll();
} else {
    $st = $pdo->prepare(
        'SELECT o.*, b.nama_barang, b.satuan, u.nama AS petugas FROM stok_opname o
         JOIN barang b ON b.kode_barang = o.kode_barang
         JOIN pengguna u ON u.id_pengguna = o.id_pengguna
         WHERE o.tanggal BETWEEN ? AND ? ORDER BY o.tanggal, o.id_opname'
    );
    $st->execute([$dari, $sampai]);
    $rows = $st->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>
<div class="card noprint">
    <form method="get">
        <div class="grid">
            <div><label>Jenis Laporan</label>
                <select name="jenis">
                    <?php foreach ($jenisList as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $k === $jenis ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>" required></div>
            <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>" required></div>
        </div>
        <div class="actions">
            <button class="g" type="submit"><?= icon('search', 16) ?> Tampilkan</button>
            <button class="m" type="button" onclick="window.print()"><?= icon('printer', 16) ?> Cetak / Simpan PDF</button>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="card-title"><?= e($jenisList[$jenis]) ?></h3>
    <p class="sub">Periode: <?= tgl($dari) ?> s.d. <?= tgl($sampai) ?></p>
    <div class="wrap"><table>
    <?php if ($jenis === 'stok'): ?>
        <tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Satuan</th><th>Awal</th><th>Masuk</th><th>Keluar</th><th>Penyesuaian</th><th>Akhir</th></tr>
        <?php foreach ($rows as $r): $b = $r['b']; ?>
            <tr><td><?= e($b['kode_barang']) ?></td><td><?= e($b['nama_barang']) ?></td><td><?= e($b['nama_kategori']) ?></td><td><?= e($b['satuan']) ?></td>
            <td><?= qty($r['awal']) ?></td><td><?= qty($r['masuk']) ?></td><td><?= qty($r['keluar']) ?></td>
            <td><?= ($r['adj'] > 0 ? '+' : '') . qty($r['adj']) ?></td><td><b><?= qty($r['akhir']) ?></b></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td class="empty" colspan="9">Belum ada data barang.</td></tr><?php endif; ?>
    <?php elseif ($jenis === 'masuk'): $total = 0; ?>
        <tr><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Harga/Satuan</th><th>Total</th><th>Pemasok</th><th>Kedaluwarsa</th><th>Barista</th><th>Dicatat oleh</th></tr>
        <?php foreach ($rows as $r): $t = (float)$r['jumlah'] * (float)$r['harga_beli']; $total += $t; ?>
            <tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama_barang']) ?></td><td><?= qty($r['jumlah']) ?> <?= e($r['satuan']) ?></td>
            <td><?= rupiah($r['harga_beli']) ?></td><td><?= rupiah($t) ?></td><td><?= e($r['nama_pemasok'] ?? '-') ?></td>
            <td><?= tgl($r['tgl_kedaluwarsa']) ?></td><td><?= e($r['nama_barista'] ?? '-') ?></td><td><?= e($r['petugas']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td class="empty" colspan="9">Tidak ada data pada periode ini.</td></tr>
        <?php else: ?><tr><td colspan="4"><b>Total pembelian</b></td><td colspan="5"><b><?= rupiah($total) ?></b></td></tr><?php endif; ?>
    <?php elseif ($jenis === 'keluar'): ?>
        <tr><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Keterangan</th><th>Barista</th><th>Dicatat oleh</th></tr>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama_barang']) ?></td><td><?= qty($r['jumlah']) ?> <?= e($r['satuan']) ?></td>
            <td><?= e($r['keterangan']) ?></td><td><?= e($r['nama_barista'] ?? '-') ?></td><td><?= e($r['petugas']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td class="empty" colspan="6">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
    <?php else: ?>
        <tr><th>Tanggal</th><th>Barang</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th><th>Status</th><th>Petugas</th></tr>
        <?php foreach ($rows as $r): $sel = (float)$r['selisih']; ?>
            <tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama_barang']) ?></td><td><?= qty($r['stok_sistem']) ?></td><td><?= qty($r['stok_fisik']) ?></td>
            <td class="<?= $sel > 0 ? 'pos' : ($sel < 0 ? 'neg' : '') ?>"><?= ($sel > 0 ? '+' : '') . qty($sel) ?></td><td><?= badge_opname($r['status_persetujuan']) ?></td><td><?= e($r['petugas']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td class="empty" colspan="7">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
    <?php endif; ?>
    </table></div>
    <?php if ($jenis === 'stok'): ?>
        <p class="hint">Penyesuaian = selisih stok opname yang sudah disetujui admin, dihitung pada tanggal pemeriksaan.</p>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
