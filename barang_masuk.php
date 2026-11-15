<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$judul = 'Barang Masuk';

$barang  = $pdo->query('SELECT kode_barang, nama_barang, satuan FROM barang ORDER BY nama_barang')->fetchAll();
$pemasok = $pdo->query('SELECT id_pemasok, nama_pemasok FROM pemasok ORDER BY nama_pemasok')->fetchAll();
$namaAkun = $pdo->query('SELECT nama FROM pengguna ORDER BY nama')->fetchAll(PDO::FETCH_COLUMN);
$errors = [];
$f = ['tanggal' => date('Y-m-d'), 'kode_barang' => '', 'jumlah' => '', 'harga_beli' => '0', 'id_pemasok' => '', 'tgl_kedaluwarsa' => '', 'nama_barista' => is_admin() ? '' : user()['nama']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $v) { $f[$k] = trim((string)($_POST[$k] ?? '')); }

    if (!valid_date($f['tanggal']) || $f['tanggal'] > date('Y-m-d')) { $errors[] = 'Tanggal tidak valid (tidak boleh melebihi hari ini).'; }
    $ada = false;
    foreach ($barang as $b) { if ($b['kode_barang'] === $f['kode_barang']) { $ada = true; } }
    if (!$ada) { $errors[] = 'Pilih barang.'; }
    if (!is_numeric($f['jumlah']) || (float)$f['jumlah'] <= 0 || (float)$f['jumlah'] > 99999999) { $errors[] = 'Jumlah harus lebih dari 0.'; }
    if (!is_numeric($f['harga_beli']) || (float)$f['harga_beli'] < 0) { $errors[] = 'Harga beli harus angka 0 atau lebih.'; }
    $idp = null;
    if ($f['id_pemasok'] !== '') {
        $idp = (int)$f['id_pemasok'];
        $okp = false;
        foreach ($pemasok as $p) { if ((int)$p['id_pemasok'] === $idp) { $okp = true; } }
        if (!$okp) { $errors[] = 'Pemasok tidak valid.'; }
    }
    if ($f['tgl_kedaluwarsa'] !== '' && !valid_date($f['tgl_kedaluwarsa'])) { $errors[] = 'Tanggal kedaluwarsa tidak valid.'; }
    if ($f['nama_barista'] === '' || mb_strlen($f['nama_barista']) > 100) { $errors[] = 'Nama barista wajib diisi (maksimal 100 karakter).'; }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO barang_masuk (kode_barang, id_pemasok, id_pengguna, tanggal, jumlah, harga_beli, tgl_kedaluwarsa, nama_barista) VALUES (?,?,?,?,?,?,?,?)');
            $st->execute([$f['kode_barang'], $idp, user()['id'], $f['tanggal'], (float)$f['jumlah'], (float)$f['harga_beli'], $f['tgl_kedaluwarsa'] !== '' ? $f['tgl_kedaluwarsa'] : null, $f['nama_barista']]);
            $pdo->prepare('UPDATE barang SET stok = stok + ? WHERE kode_barang = ?')->execute([(float)$f['jumlah'], $f['kode_barang']]);
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
        flash('Barang masuk tersimpan. Stok bertambah otomatis.');
        redirect('barang_masuk.php');
    }
}

$riwayat = $pdo->query(
    'SELECT m.*, b.nama_barang, b.satuan, p.nama_pemasok, u.nama AS petugas FROM barang_masuk m
     JOIN barang b ON b.kode_barang = m.kode_barang
     LEFT JOIN pemasok p ON p.id_pemasok = m.id_pemasok
     JOIN pengguna u ON u.id_pengguna = m.id_pengguna
     ORDER BY m.tanggal DESC, m.id_masuk DESC LIMIT 30'
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
                        <option value="<?= e($b['kode_barang']) ?>" <?= $f['kode_barang'] === $b['kode_barang'] ? 'selected' : '' ?>><?= e($b['nama_barang']) ?> (<?= e($b['satuan']) ?>)</option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Jumlah</label><input type="number" name="jumlah" step="0.01" min="0.01" value="<?= e($f['jumlah']) ?>" required></div>
            <div><label>Harga Beli per Satuan (Rp)</label><input type="number" name="harga_beli" step="1" min="0" value="<?= e($f['harga_beli']) ?>"></div>
            <div><label>Pemasok</label>
                <select name="id_pemasok">
                    <option value="">- tanpa pemasok -</option>
                    <?php foreach ($pemasok as $p): ?>
                        <option value="<?= (int)$p['id_pemasok'] ?>" <?= (string)$f['id_pemasok'] === (string)$p['id_pemasok'] ? 'selected' : '' ?>><?= e($p['nama_pemasok']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Tanggal Kedaluwarsa (jika ada)</label><input type="date" name="tgl_kedaluwarsa" value="<?= e($f['tgl_kedaluwarsa']) ?>"></div>
            <div><label>Barista yang Memasukkan</label><input name="nama_barista" list="daftar-barista" maxlength="100" value="<?= e($f['nama_barista']) ?>" placeholder="Nama barista" required>
                <datalist id="daftar-barista"><?php foreach ($namaAkun as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist></div>
        </div>
        <div class="actions">
            <button class="g" type="submit">Simpan</button>
            <button class="m" type="reset">Batal</button>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Riwayat Barang Masuk (30 terakhir)</h3>
    <div class="wrap"><table>
        <tr><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Harga/Satuan</th><th>Pemasok</th><th>Kedaluwarsa</th><th>Barista</th><th>Dicatat oleh</th></tr>
        <?php foreach ($riwayat as $r): ?>
            <tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama_barang']) ?></td>
            <td><?= qty($r['jumlah']) ?> <?= e($r['satuan']) ?></td><td><?= rupiah($r['harga_beli']) ?></td>
            <td><?= e($r['nama_pemasok'] ?? '-') ?></td><td><?= tgl($r['tgl_kedaluwarsa']) ?></td><td><?= e($r['nama_barista'] ?? '-') ?></td><td><?= e($r['petugas']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$riwayat): ?><tr><td class="empty" colspan="8">Belum ada data.</td></tr><?php endif; ?>
    </table></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
