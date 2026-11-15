<?php
require_once __DIR__ . '/includes/init.php';
require_admin();
$judul = 'Pengguna';
$errors = [];
$edit = null;

function jumlah_admin(PDO $pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM pengguna WHERE peran = 'admin'")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'simpan') {
        $id = (int)($_POST['id_pengguna'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $uname = trim($_POST['username'] ?? '');
        $peran = ($_POST['peran'] ?? '') === 'admin' ? 'admin' : 'karyawan';
        $pass = (string)($_POST['password'] ?? '');

        if ($nama === '' || mb_strlen($nama) > 100) { $errors[] = 'Nama wajib diisi (maksimal 100 karakter).'; }
        if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $uname)) { $errors[] = 'Username 3-50 karakter: huruf, angka, titik, atau garis bawah.'; }
        if ($id === 0 && strlen($pass) < 6) { $errors[] = 'Password minimal 6 karakter.'; }
        if ($id > 0 && $pass !== '' && strlen($pass) < 6) { $errors[] = 'Password baru minimal 6 karakter.'; }

        if ($id > 0 && !$errors) {
            $st = $pdo->prepare('SELECT peran FROM pengguna WHERE id_pengguna = ?');
            $st->execute([$id]);
            $lama = $st->fetch();
            if (!$lama) { $errors[] = 'Pengguna tidak ditemukan.'; }
            elseif ($lama['peran'] === 'admin' && $peran !== 'admin' && jumlah_admin($pdo) <= 1) { $errors[] = 'Harus ada minimal satu admin.'; }
        }

        if (!$errors) {
            try {
                if ($id > 0) {
                    if ($pass !== '') {
                        $pdo->prepare('UPDATE pengguna SET nama = ?, username = ?, peran = ?, password = ? WHERE id_pengguna = ?')
                            ->execute([$nama, $uname, $peran, password_hash($pass, PASSWORD_DEFAULT), $id]);
                    } else {
                        $pdo->prepare('UPDATE pengguna SET nama = ?, username = ?, peran = ? WHERE id_pengguna = ?')
                            ->execute([$nama, $uname, $peran, $id]);
                    }
                    if ($id === user()['id']) {
                        $_SESSION['user']['nama'] = $nama;
                        $_SESSION['user']['username'] = $uname;
                        $_SESSION['user']['peran'] = $peran;
                    }
                    flash('Pengguna diubah.');
                } else {
                    $pdo->prepare('INSERT INTO pengguna (nama, username, password, peran) VALUES (?,?,?,?)')
                        ->execute([$nama, $uname, password_hash($pass, PASSWORD_DEFAULT), $peran]);
                    flash('Pengguna ditambahkan.');
                }
                redirect('pengguna.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') { $errors[] = 'Username sudah digunakan.'; } else { throw $ex; }
            }
        }
        $edit = ['id_pengguna' => $id, 'nama' => $nama, 'username' => $uname, 'peran' => $peran];
    } elseif ($act === 'hapus') {
        $id = (int)($_POST['id_pengguna'] ?? 0);
        $st = $pdo->prepare('SELECT peran FROM pengguna WHERE id_pengguna = ?');
        $st->execute([$id]);
        $t = $st->fetch();
        if ($id === user()['id']) {
            flash('Anda tidak dapat menghapus akun sendiri.', 'err');
        } elseif ($t && $t['peran'] === 'admin' && jumlah_admin($pdo) <= 1) {
            flash('Harus ada minimal satu admin.', 'err');
        } elseif ($t) {
            try {
                $pdo->prepare('DELETE FROM pengguna WHERE id_pengguna = ?')->execute([$id]);
                flash('Pengguna dihapus.');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') { flash('Pengguna tidak dapat dihapus karena sudah punya riwayat transaksi.', 'err'); } else { throw $ex; }
            }
        }
        redirect('pengguna.php');
    }
}
if ($edit === null && isset($_GET['ubah'])) {
    $st = $pdo->prepare('SELECT id_pengguna, nama, username, peran FROM pengguna WHERE id_pengguna = ?');
    $st->execute([(int)$_GET['ubah']]);
    $edit = $st->fetch() ?: null;
}
$daftar = $pdo->query('SELECT id_pengguna, nama, username, peran FROM pengguna ORDER BY peran, nama')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 class="card-title"><?= $edit ? 'Ubah Pengguna' : 'Tambah Pengguna' ?></h3>
    <?php foreach ($errors as $er): ?><div class="msg err"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?><input type="hidden" name="act" value="simpan">
        <input type="hidden" name="id_pengguna" value="<?= (int)($edit['id_pengguna'] ?? 0) ?>">
        <div class="grid">
            <div><label>Nama</label><input name="nama" value="<?= e($edit['nama'] ?? '') ?>" required maxlength="100"></div>
            <div><label>Username</label><input name="username" value="<?= e($edit['username'] ?? '') ?>" required maxlength="50"></div>
            <div><label>Peran</label>
                <select name="peran">
                    <option value="karyawan" <?= ($edit['peran'] ?? '') === 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
                    <option value="admin" <?= ($edit['peran'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select></div>
            <div><label><?= $edit ? 'Password Baru (kosongkan jika tidak diganti)' : 'Password' ?></label>
                <input name="password" type="password" <?= $edit ? '' : 'required' ?> autocomplete="new-password"></div>
        </div>
        <div class="actions">
            <button class="g" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="btn m" href="pengguna.php">Batal</a><?php endif; ?>
        </div>
    </form>
</div>
<div class="card"><div class="wrap"><table>
    <tr><th>Nama</th><th>Username</th><th>Peran</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $p): ?>
        <tr><td><?= e($p['nama']) ?></td><td><?= e($p['username']) ?></td><td><span class="badge role <?= e($p['peran']) ?>"><?= e(ucfirst($p['peran'])) ?></span></td>
        <td class="nowrap"><a class="btn sm m" href="pengguna.php?ubah=<?= (int)$p['id_pengguna'] ?>">Ubah</a>
            <?php if ((int)$p['id_pengguna'] !== user()['id']): ?>
            <form method="post" class="inline" onsubmit="return confirm('Hapus pengguna ini?')">
                <?= csrf_field() ?><input type="hidden" name="act" value="hapus"><input type="hidden" name="id_pengguna" value="<?= (int)$p['id_pengguna'] ?>">
                <button class="sm r" type="submit">Hapus</button></form>
            <?php endif; ?></td></tr>
    <?php endforeach; ?>
</table></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
