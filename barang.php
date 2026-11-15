<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$judul = 'Data Barang';

$kategori = $pdo->query('SELECT * FROM kategori ORDER BY nama_kategori')->fetchAll();
$errors = [];
$mode = null;
$form = null;

function kode_berikutnya(PDO $pdo) {
    $n = (int)$pdo->query("SELECT MAX(CAST(SUBSTRING(kode_barang, 5) AS UNSIGNED)) FROM barang WHERE kode_barang REGEXP '^BRG-[0-9]+$'")->fetchColumn();
    return 'BRG-' . str_pad((string)($n + 1), 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    $act = $_POST['act'] ?? '';

    if ($act === 'simpan') {
        $mode = ($_POST['mode'] ?? '') === 'ubah' ? 'ubah' : 'tambah';
        $form = [
            'kode_barang' => strtoupper(trim($_POST['kode_barang'] ?? '')),
            'nama_barang' => trim($_POST['nama_barang'] ?? ''),
            'id_kategori' => (int)($_POST['id_kategori'] ?? 0),
            'satuan'      => trim($_POST['satuan'] ?? ''),
            'stok'        => $_POST['stok'] ?? '0',
            'stok_minimum' => $_POST['stok_minimum'] ?? '0',
        ];
        if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $form['kode_barang'])) { $errors[] = 'Kode barang 2-20 karakter (huruf, angka, tanda minus).'; }
        if ($form['nama_barang'] === '' || mb_strlen($form['nama_barang']) > 100) { $errors[] = 'Nama barang wajib diisi (maksimal 100 karakter).'; }
        if ($form['satuan'] === '' || mb_strlen($form['satuan']) > 20) { $errors[] = 'Satuan wajib diisi (maksimal 20 karakter).'; }
        $kat_ok = false;
        foreach ($kategori as $k) { if ((int)$k['id_kategori'] === $form['id_kategori']) { $kat_ok = true; } }
        if (!$kat_ok) { $errors[] = 'Pilih kategori.'; }
        if (!is_numeric($form['stok_minimum']) || (float)$form['stok_minimum'] < 0) { $errors[] = 'Stok minimum harus angka 0 atau lebih.'; }
        if ($mode === 'tambah' && (!is_numeric($form['stok']) || (float)$form['stok'] < 0)) { $errors[] = 'Stok awal harus angka 0 atau lebih.'; }

        if (!$errors) {
            try {
                if ($mode === 'tambah') {
                    $st = $pdo->prepare('INSERT INTO barang (kode_barang, nama_barang, id_kategori, satuan, stok, stok_minimum) VALUES (?,?,?,?,?,?)');
                    $st->execute([$form['kode_barang'], $form['nama_barang'], $form['id_kategori'], $form['satuan'], (float)$form['stok'], (float)$form['stok_minimum']]);
                    flash('Barang berhasil ditambahkan.');
                } else {
                    $st = $pdo->prepare('UPDATE barang SET nama_barang = ?, id_kategori = ?, satuan = ?, stok_minimum = ? WHERE kode_barang = ?');
                    $st->execute([$form['nama_barang'], $form['id_kategori'], $form['satuan'], (float)$form['stok_minimum'], $form['kode_barang']]);
                    flash('Data barang berhasil diubah.');
                }
                redirect('barang.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') { $errors[] = 'Kode barang sudah digunakan.'; } else { throw $ex; }
            }
        }
    } elseif ($act === 'hapus') {
        try {
            $st = $pdo->prepare('DELETE FROM barang WHERE kode_barang = ?');
            $st->execute([$_POST['kode_barang'] ?? '']);
            flash('Barang dihapus.');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                flash('Barang tidak dapat dihapus karena sudah punya riwayat transaksi.', 'err');
            } else { throw $ex; }
        }
        redirect('barang.php');
    }
}

$aksi = $mode ?? ($_GET['aksi'] ?? '');
if ($form === null && is_admin()) {
    if ($aksi === 'tambah') {
        $form = ['kode_barang' => kode_berikutnya($pdo), 'nama_barang' => '', 'id_kategori' => 0, 'satuan' => '', 'stok' => '0', 'stok_minimum' => '0'];
    } elseif ($aksi === 'ubah') {
        $st = $pdo->prepare('SELECT * FROM barang WHERE kode_barang = ?');
        $st->execute([$_GET['kode'] ?? '']);
        $form = $st->fetch();
        if (!$form) { redirect('barang.php'); }
    }
}
if (!is_admin()) { $form = null; }

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT b.*, k.nama_kategori FROM barang b JOIN kategori k ON k.id_kategori = b.id_kategori';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE b.kode_barang LIKE ? OR b.nama_barang LIKE ? OR k.nama_kategori LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY b.kode_barang';
$st = $pdo->prepare($sql);
$st->execute($params);
$daftar = $st->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<?php if ($form): $editing = ($aksi === 'ubah'); ?>
<div class="card">
    <h3 class="card-title"><?= $editing ? 'Ubah Barang' : 'Tambah Barang' ?></h3>
    <?php foreach ($errors as $er): ?><div class="msg err"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="simpan">
        <input type="hidden" name="mode" value="<?= $editing ? 'ubah' : 'tambah' ?>">
        <div class="grid">
            <div><label>Kode Barang</label>
                <input name="kode_barang" value="<?= e($form['kode_barang']) ?>" <?= $editing ? 'readonly' : 'required' ?>></div>
            <div><label>Nama Barang</label>
                <input name="nama_barang" value="<?= e($form['nama_barang']) ?>" required maxlength="100"></div>
            <div><label>Kategori</label>
                <select name="id_kategori" required>
                    <option value="">- pilih -</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= (int)$k['id_kategori'] ?>" <?= (int)$form['id_kategori'] === (int)$k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Satuan (kg, liter, pcs, ...)</label>
                <input name="satuan" value="<?= e($form['satuan']) ?>" required maxlength="20"></div>
            <?php if (!$editing): ?>
            <div><label>Stok Awal</label>
                <input name="stok" type="number" step="0.01" min="0" value="<?= e($form['stok']) ?>"></div>
            <?php endif; ?>
            <div><label>Stok Minimum</label>
                <input name="stok_minimum" type="number" step="0.01" min="0" value="<?= e($form['stok_minimum']) ?>" required></div>
        </div>
        <?php if ($editing): ?><p class="hint">Stok hanya berubah lewat Barang Masuk, Barang Keluar, dan Stok Opname.</p><?php endif; ?>
        <div class="actions">
            <button class="g" type="submit">Simpan</button>
            <a class="btn m" href="barang.php">Batal</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <form method="get" class="toolbar noprint">
        <div class="grow"><label for="q">Cari barang</label>
            <div class="search"><?= icon('search', 18) ?><input id="q" name="q" value="<?= e($q) ?>" placeholder="Kode, nama, atau kategori"></div></div>
        <button class="m" type="submit">Cari</button>
        <?php if (is_admin()): ?><a class="btn g" href="barang.php?aksi=tambah"><?= icon('plus', 16) ?> Tambah Barang</a><?php endif; ?>
    </form>
    <div class="wrap"><table>
        <tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Satuan</th><th>Stok</th><th>Min</th><th>Status</th><?php if (is_admin()): ?><th>Aksi</th><?php endif; ?></tr>
        <?php foreach ($daftar as $b): ?>
            <tr class="<?= is_low($b) ? 'low' : '' ?>">
                <td><?= e($b['kode_barang']) ?></td><td><?= e($b['nama_barang']) ?></td>
                <td><?= e($b['nama_kategori']) ?></td><td><?= e($b['satuan']) ?></td>
                <td class="s"><?= qty($b['stok']) ?></td><td><?= qty($b['stok_minimum']) ?></td>
                <td><?= badge_stok($b) ?></td>
                <?php if (is_admin()): ?>
                <td class="nowrap">
                    <a class="btn sm m" href="barang.php?aksi=ubah&kode=<?= urlencode($b['kode_barang']) ?>">Ubah</a>
                    <form method="post" class="inline" onsubmit="return confirm('Hapus barang ini?')">
                        <?= csrf_field() ?><input type="hidden" name="act" value="hapus">
                        <input type="hidden" name="kode_barang" value="<?= e($b['kode_barang']) ?>">
                        <button class="sm r" type="submit">Hapus</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><tr><td class="empty" colspan="8">Data barang tidak ditemukan.</td></tr><?php endif; ?>
    </table></div>
    <p class="hint warn">Angka merah = stok di bawah batas minimum.</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
