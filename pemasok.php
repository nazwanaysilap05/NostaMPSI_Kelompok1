<?php
require_once __DIR__ . '/includes/init.php';
require_admin();
$judul = 'Pemasok';
$errors = [];
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'simpan') {
        $id = (int)($_POST['id_pemasok'] ?? 0);
        $nama = trim($_POST['nama_pemasok'] ?? '');
        $kontak = trim($_POST['kontak'] ?? '');
        if ($nama === '' || mb_strlen($nama) > 100) { $errors[] = 'Nama pemasok wajib diisi (maksimal 100 karakter).'; }
        if (mb_strlen($kontak) > 50) { $errors[] = 'Kontak maksimal 50 karakter.'; }
        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE pemasok SET nama_pemasok = ?, kontak = ? WHERE id_pemasok = ?')->execute([$nama, $kontak, $id]);
                flash('Pemasok diubah.');
            } else {
                $pdo->prepare('INSERT INTO pemasok (nama_pemasok, kontak) VALUES (?,?)')->execute([$nama, $kontak]);
                flash('Pemasok ditambahkan.');
            }
            redirect('pemasok.php');
        }
        $edit = ['id_pemasok' => $id, 'nama_pemasok' => $nama, 'kontak' => $kontak];
    } elseif ($act === 'hapus') {
        try {
            $pdo->prepare('DELETE FROM pemasok WHERE id_pemasok = ?')->execute([(int)($_POST['id_pemasok'] ?? 0)]);
            flash('Pemasok dihapus.');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') { flash('Pemasok tidak dapat dihapus karena sudah dipakai di barang masuk.', 'err'); } else { throw $ex; }
        }
        redirect('pemasok.php');
    }
}
if ($edit === null && isset($_GET['ubah'])) {
    $st = $pdo->prepare('SELECT * FROM pemasok WHERE id_pemasok = ?');
    $st->execute([(int)$_GET['ubah']]);
    $edit = $st->fetch() ?: null;
}
$daftar = $pdo->query('SELECT * FROM pemasok ORDER BY nama_pemasok')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 class="card-title"><?= $edit ? 'Ubah Pemasok' : 'Tambah Pemasok' ?></h3>
    <?php foreach ($errors as $er): ?><div class="msg err"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="act" value="simpan">
        <input type="hidden" name="id_pemasok" value="<?= (int)($edit['id_pemasok'] ?? 0) ?>">
        <div class="grid">
            <div><label>Nama Pemasok</label><input name="nama_pemasok" value="<?= e($edit['nama_pemasok'] ?? '') ?>" required maxlength="100"></div>
            <div><label>Kontak (telepon/WA)</label><input name="kontak" value="<?= e($edit['kontak'] ?? '') ?>" maxlength="50"></div>
        </div>
        <div class="actions">
            <button class="g" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="btn m" href="pemasok.php">Batal</a><?php endif; ?>
        </div>
    </form>
</div>
<div class="card"><div class="wrap"><table>
    <tr><th>Nama Pemasok</th><th>Kontak</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $p): ?>
        <tr><td><?= e($p['nama_pemasok']) ?></td><td><?= e($p['kontak']) ?></td>
        <td class="nowrap"><a class="btn sm m" href="pemasok.php?ubah=<?= (int)$p['id_pemasok'] ?>">Ubah</a>
            <form method="post" class="inline" onsubmit="return confirm('Hapus pemasok ini?')">
                <?= csrf_field() ?><input type="hidden" name="act" value="hapus"><input type="hidden" name="id_pemasok" value="<?= (int)$p['id_pemasok'] ?>">
                <button class="sm r" type="submit">Hapus</button></form></td></tr>
    <?php endforeach; ?>
</table></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
