<?php
require_once __DIR__ . '/includes/init.php';
if (user()) { redirect('index.php'); }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['gagal'] = $_SESSION['gagal'] ?? 0;
    $_SESSION['kunci'] = $_SESSION['kunci'] ?? 0;
    if (time() < $_SESSION['kunci']) {
        $err = 'Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.';
    } else {
        $uname = trim($_POST['username'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $st = $pdo->prepare('SELECT * FROM pengguna WHERE username = ?');
        $st->execute([$uname]);
        $row = $st->fetch();
        if ($row && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$row['id_pengguna'], 'nama' => $row['nama'],
                'username' => $row['username'], 'peran' => $row['peran'],
            ];
            $_SESSION['gagal'] = 0;
            redirect('index.php');
        }
        $_SESSION['gagal']++;
        if ($_SESSION['gagal'] >= 5) {
            $_SESSION['kunci'] = time() + 60;
            $_SESSION['gagal'] = 0;
        }
        $err = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="assets/favicon.png">
<link rel="shortcut icon" href="assets/favicon.ico">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
<meta name="theme-color" content="#4b5d25">
<link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
<main class="login-wrap">
    <div class="login-card">
        <div class="login-head">
            <img src="assets/logo-light.png" alt="Logo Nosta" width="92" height="92">
            <h1>NOSTA COFFEE</h1>
            <p>Heritage House</p>
        </div>
        <div class="login-body">
            <h2>Silakan Login</h2>
            <p class="sub"><?= e(APP_SUB) ?></p>
            <?php if ($err): ?><div class="msg err" role="alert"><?= icon('alert', 18) ?><span><?= e($err) ?></span></div><?php endif; ?>
            <form method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Username</label>
                    <?= icon('user', 18) ?>
                    <input id="username" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <?= icon('lock', 18) ?>
                    <input id="password" name="password" type="password" required>
                </div>
                <label class="check">
                    <input type="checkbox" onclick="document.getElementById('password').type = this.checked ? 'text' : 'password'">
                    Lihat password
                </label>
                <div class="actions">
                    <button type="submit" class="g">Login</button>
                    <button type="reset" class="m">Batal</button>
                </div>
            </form>
        </div>
    </div>
    <div class="login-foot">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></div>
</main>
</body>
</html>
