<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$judul = 'Stok Opname';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    // ---- simpan hasil hitung fisik ----
    if ($act === 'simpan') {
        $tanggal = trim($_POST['tanggal'] ?? '');
        $fisik = $_POST['fisik'] ?? [];
        if (!valid_date($tanggal) || $tanggal > date('Y-m-d')) {
            flash('Tanggal pemeriksaan tidak valid.', 'err');
            redirect('stok_opname.php');
        }
        if (!is_array($fisik)) { $fisik = []; }
        $jumlahCatat = 0;
        $pdo->beginTransaction();
        try {
            $ambil = $pdo->prepare('SELECT stok FROM barang WHERE kode_barang = ? FOR UPDATE');
            $simpan = $pdo->prepare('INSERT INTO stok_opname (kode_barang, id_pengguna, tanggal, stok_sistem, stok_fisik, selisih, status_persetujuan) VALUES (?,?,?,?,?,?,?)');
            foreach ($fisik as $kode => $val) {
                $val = trim((string)$val);
                if ($val === '') { continue; }
                if (!is_numeric($val) || (float)$val < 0 || (float)$val > 99999999) {
                    $pdo->rollBack();
                    flash('Stok fisik harus berupa angka 0 atau lebih.', 'err');
                    redirect('stok_opname.php');
                }
                $ambil->execute([(string)$kode]);
                $row = $ambil->fetch();
                if (!$row) { continue; }
                $sistem = round((float)$row['stok'], 2);
                $fis = round((float)$val, 2);
                $selisih = round($fis - $sistem, 2);
                $status = abs($selisih) < 0.005 ? 'sesuai' : 'menunggu';
                $simpan->execute([(string)$kode, user()['id'], $tanggal, $sistem, $fis, $selisih, $status]);
                $jumlahCatat++;
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $ex;
        }
        flash($jumlahCatat > 0 ? "Hasil stok opname tersimpan ($jumlahCatat barang). Selisih menunggu persetujuan admin." : 'Tidak ada stok fisik yang diisi.', $jumlahCatat > 0 ? 'ok' : 'err');
        redirect('stok_opname.php');
    }

    // ---- persetujuan / penolakan (admin) ----
    if ($act === 'setujui' || $act === 'tolak') {
        require_admin();
        $id = (int)($_POST['id_opname'] ?? 0);
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM stok_opname WHERE id_opname = ? FOR UPDATE');
            $st->execute([$id]);
            $o = $st->fetch();
            if (!$o || $o['status_persetujuan'] !== 'menunggu') {
                $pdo->rollBack();
                flash('Data tidak ditemukan atau sudah diproses.', 'err');
                redirect('stok_opname.php');
            }
            if ($act === 'setujui') {
                $up = $pdo->prepare('UPDATE barang SET stok = stok + ? WHERE kode_barang = ? AND stok + ? >= 0');
                $up->execute([(float)$o['selisih'], $o['kode_barang'], (float)$o['selisih']]);
                if ($up->rowCount() === 0) {
                    $pdo->rollBack();
                    flash('Penyesuaian dibatalkan karena akan membuat stok menjadi negatif.', 'err');
                    redirect('stok_opname.php');
                }
                $pdo->prepare("UPDATE stok_opname SET status_persetujuan = 'disetujui' WHERE id_opname = ?")->execute([$id]);
                flash('Penyesuaian stok disetujui dan stok sudah diperbarui.');
            } else {
                $pdo->prepare("UPDATE stok_opname SET status_persetujuan = 'ditolak' WHERE id_opname = ?")->execute([$id]);
                flash('Penyesuaian ditolak. Stok tidak berubah.');
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $ex;
        }
        redirect('stok_opname.php');
    }
}

$barang = $pdo->query('SELECT b.kode_barang, b.nama_barang, b.satuan, b.stok FROM barang b ORDER BY b.nama_barang')->fetchAll();
$riwayat = $pdo->query(
    'SELECT o.*, b.nama_barang, b.satuan, u.nama AS petugas FROM stok_opname o
     JOIN barang b ON b.kode_barang = o.kode_barang
     JOIN pengguna u ON u.id_pengguna = o.id_pengguna
     ORDER BY o.id_opname DESC LIMIT 50'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 class="card-title">Input Hasil Hitung Fisik</h3>
    <p class="sub">Isi kolom "Stok Fisik" hanya untuk barang yang dihitung. Kolom kosong dilewati.</p>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="act" value="simpan">
        <label>Tanggal Pemeriksaan</label>
        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required class="w-date">
        <div class="wrap mt"><table>
            <tr><th>Barang</th><th>Stok Sistem</th><th>Stok Fisik</th></tr>
            <?php foreach ($barang as $b): ?>
                <tr><td><?= e($b['nama_barang']) ?> <small>(<?= e($b['kode_barang']) ?>)</small></td>
                <td><?= qty($b['stok']) ?> <?= e($b['satuan']) ?></td>
                <td><input type="number" step="0.01" min="0" name="fisik[<?= e($b['kode_barang']) ?>]" class="opname-input" aria-label="Stok fisik <?= e($b['nama_barang']) ?>"></td></tr>
            <?php endforeach; ?>
        </table></div>
        <div class="actions"><button class="g" type="submit"><?= icon('check', 16) ?> Simpan Hasil</button></div>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Riwayat dan Persetujuan</h3>
    <?php if (!is_admin()): ?><p class="sub">Penyesuaian stok hanya dapat disetujui oleh admin.</p><?php endif; ?>
    <div class="wrap"><table>
        <tr><th>Tanggal</th><th>Barang</th><th>Sistem</th><th>Fisik</th><th>Selisih</th><th>Status</th><th>Petugas</th><?php if (is_admin()): ?><th>Aksi</th><?php endif; ?></tr>
        <?php foreach ($riwayat as $o): $sel = (float)$o['selisih']; ?>
            <tr><td><?= tgl($o['tanggal']) ?></td><td><?= e($o['nama_barang']) ?></td>
            <td><?= qty($o['stok_sistem']) ?></td><td><?= qty($o['stok_fisik']) ?></td>
            <td class="<?= $sel > 0 ? 'pos' : ($sel < 0 ? 'neg' : '') ?>"><?= ($sel > 0 ? '+' : '') . qty($sel) ?></td>
            <td><?= badge_opname($o['status_persetujuan']) ?></td><td><?= e($o['petugas']) ?></td>
            <?php if (is_admin()): ?>
            <td class="nowrap">
                <?php if ($o['status_persetujuan'] === 'menunggu'): ?>
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="act" value="setujui"><input type="hidden" name="id_opname" value="<?= (int)$o['id_opname'] ?>"><button class="sm g" type="submit">Setujui</button></form>
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="act" value="tolak"><input type="hidden" name="id_opname" value="<?= (int)$o['id_opname'] ?>"><button class="sm r" type="submit">Tolak</button></form>
                <?php endif; ?>
            </td>
            <?php endif; ?></tr>
        <?php endforeach; ?>
        <?php if (!$riwayat): ?><tr><td class="empty" colspan="8">Belum ada pemeriksaan.</td></tr><?php endif; ?>
    </table></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
