<?php
require_once __DIR__ . '/includes/init.php';
require_admin();
$judul = 'Kategori Barang';
$errors = [];
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'simpan') {
        $id = (int)($_POST['id_kategori'] ?? 0);
        $nama = trim($_POST['nama_kategori'] ?? '');
        if ($nama === '' || mb_strlen($nama) > 60) { $errors[] = 'Nama kategori wajib diisi (maksimal 60 karakter).'; }
        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?')->execute([$nama, $id]);
                flash('Kategori diubah.');
            } else {
                $pdo->prepare('INSERT INTO kategori (nama_kategori) VALUES (?)')->execute([$nama]);
                flash('Kategori ditambahkan.');
            }
            redirect('kategori.php');
        }
        $edit = ['id_kategori' => $id, 'nama_kategori' => $nama];
    } elseif ($act === 'hapus') {
        try {
            $pdo->prepare('DELETE FROM kategori WHERE id_kategori = ?')->execute([(int)($_POST['id_kategori'] ?? 0)]);
            flash('Kategori dihapus.');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') { flash('Kategori tidak dapat dihapus karena masih dipakai barang.', 'err'); } else { throw $ex; }
        }
        redirect('kategori.php');
    }
}
if ($edit === null && isset($_GET['ubah'])) {
    $st = $pdo->prepare('SELECT * FROM kategori WHERE id_kategori = ?');
    $st->execute([(int)$_GET['ubah']]);
    $edit = $st->fetch() ?: null;
}
$daftar = $pdo->query('SELECT k.*, (SELECT COUNT(*) FROM barang b WHERE b.id_kategori = k.id_kategori) AS jml FROM kategori k ORDER BY nama_kategori')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 class="card-title"><?= $edit ? 'Ubah Kategori' : 'Tambah Kategori' ?></h3>
    <?php foreach ($errors as $er): ?><div class="msg err"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="act" value="simpan">
        <input type="hidden" name="id_kategori" value="<?= (int)($edit['id_kategori'] ?? 0) ?>">
        <label>Nama Kategori</label>
        <input name="nama_kategori" value="<?= e($edit['nama_kategori'] ?? '') ?>" required maxlength="60">
        <div class="actions">
            <button class="g" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="btn m" href="kategori.php">Batal</a><?php endif; ?>
        </div>
    </form>
</div>
<div class="card"><div class="wrap"><table>
    <tr><th>Nama Kategori</th><th>Jumlah Barang</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $k): ?>
        <tr><td><?= e($k['nama_kategori']) ?></td><td><?= (int)$k['jml'] ?> barang</td>
        <td class="nowrap"><a class="btn sm m" href="kategori.php?ubah=<?= (int)$k['id_kategori'] ?>">Ubah</a>
            <form method="post" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                <?= csrf_field() ?><input type="hidden" name="act" value="hapus"><input type="hidden" name="id_kategori" value="<?= (int)$k['id_kategori'] ?>">
                <button class="sm r" type="submit">Hapus</button></form></td></tr>
    <?php endforeach; ?>
</table></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
