<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
require_login();
$user = fetch_current_user($koneksi);
$error = '';
$success = isset($_GET['saved']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(post_string('username'));
    $email = trim(post_string('email'));
    $tanggal = post_string('tanggal');

    if (!verify_csrf_token()) {
        $error = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif (strlen($username) < 3 || strlen($username) > 40) {
        $error = 'Username harus terdiri dari 3 sampai 40 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan alamat email yang valid.';
    } elseif (!DateTime::createFromFormat('!Y-m-d', $tanggal) || DateTime::createFromFormat('!Y-m-d', $tanggal)->format('Y-m-d') !== $tanggal) {
        $error = 'Masukkan tanggal lahir yang valid.';
    } else {
        $statement = mysqli_prepare($koneksi, 'SELECT id FROM users WHERE (nama = ? OR email = ?) AND id <> ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 'ssi', $username, $email, $_SESSION['user_id']);
        mysqli_stmt_execute($statement);
        $existing = mysqli_stmt_get_result($statement);
        $is_taken = mysqli_num_rows($existing) > 0;
        mysqli_stmt_close($statement);

        if ($is_taken) {
            $error = 'Username atau email sudah digunakan oleh akun lain.';
        } else {
            $statement = mysqli_prepare($koneksi, 'UPDATE users SET nama = ?, email = ?, tanggal_lahir = ? WHERE id = ?');
            mysqli_stmt_bind_param($statement, 'sssi', $username, $email, $tanggal, $_SESSION['user_id']);
            mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);
            $_SESSION['nama'] = $username;
            header('Location: profile.php?saved=1');
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
    <title>Profil | MyWibu</title>
    <script src="../../js/theme.js"></script>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/auth.css">
</head>
<body class="app-page">
    <header class="app-header">
        <a class="brand" href="../index.php"><span class="brand-mark">✦</span> MyWibu</a>
        <nav class="app-nav" aria-label="Navigasi utama">
            <a href="../index.php">Beranda</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="library.php">Library</a>
            <a class="active" href="profile.php">Profil</a>
            <?php if ($user['role'] === 'admin'): ?><a href="../admin/admin.php">Admin</a><?php endif; ?>
        </nav>
        <div class="header-user"><span class="avatar"><?= h(strtoupper(substr($user['nama'], 0, 1))) ?></span><span><?= h($user['nama']) ?></span><form method="post" action="../auth/logout.php"><input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>"><button class="logout-link" type="submit">Keluar</button></form></div>
    </header>
    <main class="app-main">
        <section class="page-heading"><div><span class="eyebrow">AKUN MYWIBU</span><h1>Profil kamu</h1><p>Kelola informasi yang digunakan pada akunmu.</p></div></section>
        <?php if ($success): ?><div class="notice notice-success" role="status">Profil berhasil diperbarui.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <section class="content-card profile-card">
            <div class="profile-avatar"><?= h(strtoupper(substr($user['nama'], 0, 1))) ?></div>
            <div class="section-heading"><div><span class="eyebrow">INFORMASI PRIBADI</span><h2>Detail akun</h2></div></div>
            <form method="post" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" minlength="3" maxlength="40" required value="<?= h(post_string('username') ?: $user['nama']) ?>">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required value="<?= h(post_string('email') ?: $user['email']) ?>">
                <label for="tanggal">Tanggal lahir</label>
                <input id="tanggal" name="tanggal" type="date" required value="<?= h(post_string('tanggal') ?: $user['tanggal_lahir']) ?>">
                <button class="button button-primary" type="submit">Simpan perubahan <span aria-hidden="true">→</span></button>
            </form>
        </section>
    </main>
</body>
</html>
