<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$judul = 'Barang Keluar';

$barang = $pdo->query('SELECT kode_barang, nama_barang, satuan, stok FROM barang ORDER BY nama_barang')->fetchAll();
$alasan = ['Pemakaian', 'Rusak', 'Kedaluwarsa'];
$namaAkun = $pdo->query('SELECT nama FROM pengguna ORDER BY nama')->fetchAll(PDO::FETCH_COLUMN);
$errors = [];
$f = ['tanggal' => date('Y-m-d'), 'kode_barang' => '', 'jumlah' => '', 'keterangan' => 'Pemakaian', 'nama_barista' => is_admin() ? '' : user()['nama']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $v) { $f[$k] = trim((string)($_POST[$k] ?? '')); }

    if (!valid_date($f['tanggal']) || $f['tanggal'] > date('Y-m-d')) { $errors[] = 'Tanggal tidak valid (tidak boleh melebihi hari ini).'; }
    $ada = false;
    foreach ($barang as $b) { if ($b['kode_barang'] === $f['kode_barang']) { $ada = true; } }
    if (!$ada) { $errors[] = 'Pilih barang.'; }
    if (!is_numeric($f['jumlah']) || (float)$f['jumlah'] <= 0 || (float)$f['jumlah'] > 99999999) { $errors[] = 'Jumlah harus lebih dari 0.'; }
    if (!in_array($f['keterangan'], $alasan, true)) { $errors[] = 'Keterangan tidak valid.'; }
    if ($f['nama_barista'] === '' || mb_strlen($f['nama_barista']) > 100) { $errors[] = 'Nama barista wajib diisi (maksimal 100 karakter).'; }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT stok, satuan FROM barang WHERE kode_barang = ? FOR UPDATE');
            $st->execute([$f['kode_barang']]);
            $row = $st->fetch();
            if (!$row || (float)$f['jumlah'] > (float)$row['stok']) {
                $pdo->rollBack();
                $errors[] = 'Jumlah melebihi stok tersedia (' . qty($row['stok'] ?? 0) . ' ' . ($row['satuan'] ?? '') . ').';
            } else {
                $st = $pdo->prepare('INSERT INTO barang_keluar (kode_barang, id_pengguna, tanggal, jumlah, keterangan, nama_barista) VALUES (?,?,?,?,?,?)');
                $st->execute([$f['kode_barang'], user()['id'], $f['tanggal'], (float)$f['jumlah'], $f['keterangan'], $f['nama_barista']]);
                $pdo->prepare('UPDATE barang SET stok = stok - ? WHERE kode_barang = ?')->execute([(float)$f['jumlah'], $f['kode_barang']]);
                $pdo->commit();
                flash('Barang keluar tersimpan. Stok berkurang otomatis.');
                redirect('barang_keluar.php');
            }
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $ex;
        }
    }
}

$riwayat = $pdo->query(
    'SELECT k.*, b.nama_barang, b.satuan, u.nama AS petugas FROM barang_keluar k
     JOIN barang b ON b.kode_barang = k.kode_barang
     JOIN pengguna u ON u.id_pengguna = k.id_pengguna
     ORDER BY k.tanggal DESC, k.id_keluar DESC LIMIT 30'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <?php foreach ($errors as $er): ?><div class="msg err"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="grid">
            <div><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($f['tanggal']) ?>" max="<?= date('Y-m-d') ?>" required></div>
            <div><label>Nama Barang</label>
                <select name="kode_barang" required>
                    <option value="">- pilih barang -</option>
                    <?php foreach ($barang as $b): ?>
                        <option value="<?= e($b['kode_barang']) ?>" <?= $f['kode_barang'] === $b['kode_barang'] ? 'selected' : '' ?>><?= e($b['nama_barang']) ?> (stok <?= qty($b['stok']) ?> <?= e($b['satuan']) ?>)</option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Jumlah Keluar</label><input type="number" name="jumlah" step="0.01" min="0.01" value="<?= e($f['jumlah']) ?>" required></div>
            <div><label>Keterangan</label>
                <select name="keterangan">
                    <?php foreach ($alasan as $a): ?>
                        <option <?= $f['keterangan'] === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Barista yang Mengambil</label><input name="nama_barista" list="daftar-barista" maxlength="100" value="<?= e($f['nama_barista']) ?>" placeholder="Nama barista" required>
                <datalist id="daftar-barista"><?php foreach ($namaAkun as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist></div>
        </div>
        <p class="hint warn">Sistem menolak jika jumlah melebihi stok yang tersedia.</p>
        <div class="actions">
            <button class="g" type="submit">Simpan</button>
            <button class="m" type="reset">Batal</button>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Riwayat Barang Keluar (30 terakhir)</h3>
    <div class="wrap"><table>
        <tr><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Keterangan</th><th>Barista</th><th>Dicatat oleh</th></tr>
        <?php foreach ($riwayat as $r): ?>
            <tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama_barang']) ?></td>
            <td><?= qty($r['jumlah']) ?> <?= e($r['satuan']) ?></td><td><?= e($r['keterangan']) ?></td><td><?= e($r['nama_barista'] ?? '-') ?></td><td><?= e($r['petugas']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$riwayat): ?><tr><td class="empty" colspan="6">Belum ada data.</td></tr><?php endif; ?>
    </table></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
