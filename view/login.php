<?php
require_once 'auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Sesi formulir sudah kedaluwarsa. Silakan coba lagi.';
    } else {
        $identity = trim(post_string('identity'));
        $password = post_string('password');

        $statement = mysqli_prepare($koneksi, 'SELECT id, nama, password FROM users WHERE nama = ? OR email = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 'ss', $identity, $identity);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['nama'] = $user['nama'];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Username/email atau password tidak cocok.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#160d27">
    <title>Masuk | MyWibu</title>
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-art">
            <a class="brand" href="index.php"><span class="brand-mark">✦</span> MyWibu</a>
            <div class="art-copy">
                <span class="eyebrow">YOUR ANIME, YOUR STORY</span>
                <h1>Setiap cerita punya tempatnya.</h1>
                <p>Simpan anime favorit, catat perjalanan menonton, dan temukan lagi cerita yang kamu suka.</p>
            </div>
            <div class="art-orbit orbit-one"></div>
            <div class="art-orbit orbit-two"></div>
            <div class="art-star">✦</div>
            <div class="art-caption">A little home for your anime world</div>
        </section>
        <section class="auth-panel">
            <div class="auth-form-wrap">
                <p class="mobile-brand"><a class="brand" href="index.php"><span class="brand-mark">✦</span> MyWibu</a></p>
                <span class="eyebrow">SELAMAT DATANG KEMBALI</span>
                <h2>Masuk ke akunmu</h2>
                <p class="form-intro">Lanjutkan mengatur daftar anime kesukaanmu.</p>
                <?php if ($error !== ''): ?>
                    <div class="notice notice-error" role="alert"><?= h($error) ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['registered'])): ?>
                    <div class="notice notice-success" role="status">Akun berhasil dibuat. Silakan masuk untuk melanjutkan.</div>
                <?php endif; ?>
                <form method="post" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                    <label for="identity">Username atau email</label>
                    <input id="identity" name="identity" type="text" autocomplete="username" placeholder="contoh: haru atau nama@email.com" required value="<?= h(post_string('identity')) ?>">

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required>

                    <button class="button button-primary" type="submit">Masuk <span aria-hidden="true">→</span></button>
                </form>
                <p class="auth-switch">Belum punya akun? <a href="signup.php">Buat akun</a></p>
                <a class="back-link" href="index.php">← Kembali ke beranda</a>
            </div>
        </section>
    </main>
</body>
</html>
