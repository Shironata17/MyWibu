<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
require_login();
ensure_library_table($koneksi);
$user = fetch_current_user($koneksi);
$statuses = ['Plan to Watch', 'Watching', 'Completed', 'On Hold', 'Dropped'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif (isset($_POST['add_anime'])) {
        $title = trim(post_string('title'));
        $status = post_string('status') ?: 'Plan to Watch';

        if ($title === '' || strlen($title) > 160) {
            $error = 'Judul anime wajib diisi dan maksimal 160 karakter.';
        } elseif (!in_array($status, $statuses, true)) {
            $error = 'Status yang dipilih tidak valid.';
        } else {
            $statement = mysqli_prepare($koneksi, 'INSERT INTO anime_library (user_id, title, status) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($statement, 'iss', $_SESSION['user_id'], $title, $status);
            mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);
            header('Location: library.php?added=1');
            exit;
        }
    } elseif (isset($_POST['delete_anime'])) {
        $anime_id = filter_input(INPUT_POST, 'anime_id', FILTER_VALIDATE_INT);
        if (!$anime_id) {
            $error = 'Anime yang dipilih tidak valid.';
        } else {
            $statement = mysqli_prepare($koneksi, 'DELETE FROM anime_library WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($statement, 'ii', $anime_id, $_SESSION['user_id']);
            mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);
            header('Location: library.php?removed=1');
            exit;
        }
    } elseif (isset($_POST['update_status'])) {
        $anime_id = filter_input(INPUT_POST, 'anime_id', FILTER_VALIDATE_INT);
        $status = post_string('status');
        if (!$anime_id || !in_array($status, $statuses, true)) {
            $error = 'Pilihan anime atau status tidak valid.';
        } else {
            $statement = mysqli_prepare($koneksi, 'UPDATE anime_library SET status = ? WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($statement, 'sii', $status, $anime_id, $_SESSION['user_id']);
            mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);
            header('Location: library.php?updated=1');
            exit;
        }
    }
}

$statement = mysqli_prepare($koneksi, 'SELECT id, title, status FROM anime_library WHERE user_id = ? ORDER BY id DESC');
mysqli_stmt_bind_param($statement, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($statement);
$anime_list = mysqli_stmt_get_result($statement);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library | MyWibu</title>
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
            <a class="active" href="library.php">Library</a>
            <a href="profile.php">Profil</a>
            <?php if ($user['role'] === 'admin'): ?><a href="../admin/admin.php">Admin</a><?php endif; ?>
        </nav>
        <div class="header-user"><span class="avatar"><?= h(strtoupper(substr($user['nama'], 0, 1))) ?></span><span><?= h($user['nama']) ?></span><form method="post" action="../auth/logout.php"><input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>"><button class="logout-link" type="submit">Keluar</button></form></div>
    </header>
    <main class="app-main">
        <section class="page-heading"><div><span class="eyebrow">KOLEKSI PRIBADI</span><h1>Anime library</h1><p>Catat judul dan pantau progres tontonanmu.</p></div></section>
        <?php if (isset($_GET['added'])): ?><div class="notice notice-success" role="status">Anime berhasil ditambahkan.</div><?php endif; ?>
        <?php if (isset($_GET['updated'])): ?><div class="notice notice-success" role="status">Status tontonan berhasil diperbarui.</div><?php endif; ?>
        <?php if (isset($_GET['removed'])): ?><div class="notice notice-success" role="status">Anime berhasil dihapus dari library.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <section class="content-card add-card">
            <div class="section-heading"><div><span class="eyebrow">TAMBAHKAN KOLEKSI</span><h2>Anime baru</h2></div></div>
            <form method="post" class="add-anime-form">
                <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                <label class="visually-hidden" for="title">Judul anime</label>
                <input id="title" name="title" type="text" maxlength="160" placeholder="Masukkan judul anime..." required>
                <label class="visually-hidden" for="new-status">Status</label>
                <select id="new-status" name="status">
                    <?php foreach ($statuses as $status): ?><option value="<?= h($status) ?>"><?= h($status) ?></option><?php endforeach; ?>
                </select>
                <button class="button button-primary" type="submit" name="add_anime">Tambah anime <span aria-hidden="true">+</span></button>
            </form>
        </section>
        <section class="content-card">
            <div class="section-heading"><div><span class="eyebrow">DAFTAR TONTONAN</span><h2>Semua anime</h2></div><span class="count-badge"><?= mysqli_num_rows($anime_list) ?> judul</span></div>
            <?php if (mysqli_num_rows($anime_list) === 0): ?>
                <div class="empty-state"><span aria-hidden="true">✦</span><h3>Belum ada anime tersimpan</h3><p>Gunakan formulir di atas untuk menambahkan judul pertamamu.</p></div>
            <?php else: ?>
                <div class="anime-list">
                    <?php while ($anime = mysqli_fetch_assoc($anime_list)): ?>
                        <article class="anime-row">
                            <span class="anime-cover" aria-hidden="true">✦</span>
                            <div class="anime-info"><h3><?= h($anime['title']) ?></h3><span class="status-pill"><?= h($anime['status']) ?></span></div>
                            <div class="anime-actions">
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                                    <input type="hidden" name="anime_id" value="<?= (int) $anime['id'] ?>">
                                    <label class="visually-hidden" for="status-<?= (int) $anime['id'] ?>">Ubah status <?= h($anime['title']) ?></label>
                                    <select id="status-<?= (int) $anime['id'] ?>" name="status">
                                        <?php foreach ($statuses as $status): ?><option value="<?= h($status) ?>" <?= $anime['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?>
                                    </select>
                                    <button class="button button-small" type="submit" name="update_status">Simpan</button>
                                </form>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                                    <input type="hidden" name="anime_id" value="<?= (int) $anime['id'] ?>">
                                    <button class="button button-small button-danger" type="submit" name="delete_anime">Hapus</button>
                                </form>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
