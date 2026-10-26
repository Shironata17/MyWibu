<?php
require_once 'auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(post_string('username'));
    $email = trim(post_string('email'));
    $password = post_string('password');
    $tanggal = post_string('tanggal');

    if (!verify_csrf_token()) {
        $error = 'Sesi formulir sudah kedaluwarsa. Silakan coba lagi.';
    } elseif (strlen($username) < 3 || strlen($username) > 40) {
        $error = 'Username harus terdiri dari 3 sampai 40 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan alamat email yang valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal terdiri dari 8 karakter.';
    } elseif (!DateTime::createFromFormat('!Y-m-d', $tanggal) || DateTime::createFromFormat('!Y-m-d', $tanggal)->format('Y-m-d') !== $tanggal) {
        $error = 'Tanggal lahir wajib diisi.';
    } else {
        $statement = mysqli_prepare($koneksi, 'SELECT id FROM users WHERE nama = ? OR email = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 'ss', $username, $email);
        mysqli_stmt_execute($statement);
        $existing = mysqli_stmt_get_result($statement);
        $is_taken = mysqli_num_rows($existing) > 0;
        mysqli_stmt_close($statement);

        if ($is_taken) {
            $error = 'Username atau email sudah digunakan.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $statement = mysqli_prepare($koneksi, 'INSERT INTO users (nama, email, password, tanggal_lahir) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($statement, 'ssss', $username, $email, $password_hash, $tanggal);
            mysqli_stmt_execute($statement);
            $user_id = mysqli_insert_id($koneksi);
            mysqli_stmt_close($statement);

            if ($user_id < 1) {
                throw new RuntimeException('Akun berhasil disimpan tetapi ID pengguna tidak tersedia.');
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['nama'] = $username;
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#160d27">
    <title>Buat Akun | MyWibu</title>
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-art">
            <a class="brand" href="index.php"><span class="brand-mark">✦</span> MyWibu</a>
            <div class="art-copy">
                <span class="eyebrow">START YOUR COLLECTION</span>
                <h1>Temukan kembali cerita favoritmu.</h1>
                <p>Satu akun untuk semua watchlist, rekomendasi, dan kenangan anime-mu.</p>
            </div>
            <div class="art-orbit orbit-one"></div>
            <div class="art-orbit orbit-two"></div>
            <div class="art-star">✦</div>
            <div class="art-caption">Make your list, make your world</div>
        </section>
        <section class="auth-panel">
            <div class="auth-form-wrap">
                <p class="mobile-brand"><a class="brand" href="index.php"><span class="brand-mark">✦</span> MyWibu</a></p>
                <span class="eyebrow">GABUNG BERSAMA KAMI</span>
                <h2>Buat akun baru</h2>
                <p class="form-intro">Mulai bangun dunia anime versimu sendiri.</p>
                <?php if ($error !== ''): ?>
                    <div class="notice notice-error" role="alert"><?= h($error) ?></div>
                <?php endif; ?>
                <form method="post" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" minlength="3" maxlength="40" placeholder="Nama panggilanmu" required value="<?= h(post_string('username')) ?>">

                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" placeholder="nama@email.com" required value="<?= h(post_string('email')) ?>">

                    <label for="password">Password <span class="label-hint">min. 8 karakter</span></label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="Buat password yang kuat" required>

                    <label for="tanggal">Tanggal lahir</label>
                    <input id="tanggal" name="tanggal" type="date" autocomplete="bday" required value="<?= h(post_string('tanggal')) ?>">

                    <button class="button button-primary" type="submit">Buat akun <span aria-hidden="true">→</span></button>
                </form>
                <p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk</a></p>
                <a class="back-link" href="index.php">← Kembali ke beranda</a>
            </div>
        </section>
    </main>
</body>
</html>
