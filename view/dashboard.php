<?php
require_once 'auth.php';
require_login();
ensure_library_table($koneksi);
$user = fetch_current_user($koneksi);

$statement = mysqli_prepare($koneksi, 'SELECT COUNT(*) AS total, SUM(status = ?) AS watching, SUM(status = ?) AS completed FROM anime_library WHERE user_id = ?');
$watching_status = 'Watching';
$completed_status = 'Completed';
mysqli_stmt_bind_param($statement, 'ssi', $watching_status, $completed_status, $_SESSION['user_id']);
mysqli_stmt_execute($statement);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
mysqli_stmt_close($statement);

$statement = mysqli_prepare($koneksi, 'SELECT id, title, status FROM anime_library WHERE user_id = ? ORDER BY id DESC LIMIT 4');
mysqli_stmt_bind_param($statement, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($statement);
$recent_anime = mysqli_stmt_get_result($statement);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MyWibu</title>
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="app-page">
    <header class="app-header">
        <a class="brand" href="dashboard.php"><span class="brand-mark">✦</span> MyWibu</a>
        <nav class="app-nav" aria-label="Navigasi utama">
            <a class="active" href="dashboard.php">Dashboard</a>
            <a href="library.php">Library</a>
            <a href="profile.php">Profil</a>
        </nav>
        <div class="header-user"><span class="avatar"><?= h(strtoupper(substr($user['nama'], 0, 1))) ?></span><span><?= h($user['nama']) ?></span><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>"><button class="logout-link" type="submit">Keluar</button></form></div>
    </header>
    <main class="app-main">
        <section class="welcome-banner">
            <div>
                <span class="eyebrow">RUANG ANIME-MU</span>
                <h1>Halo, <?= h($user['nama']) ?> <span aria-hidden="true">✦</span></h1>
                <p>Semua cerita yang kamu ikuti, tersimpan rapi di satu tempat.</p>
                <a class="button button-light" href="library.php">Buka library <span aria-hidden="true">→</span></a>
            </div>
            <div class="banner-decoration" aria-hidden="true">✧</div>
        </section>
        <section class="stats-grid" aria-label="Statistik library">
            <article class="stat-card"><span class="stat-icon">▤</span><p>Total anime</p><strong><?= (int) ($stats['total'] ?? 0) ?></strong></article>
            <article class="stat-card"><span class="stat-icon">▶</span><p>Sedang ditonton</p><strong><?= (int) ($stats['watching'] ?? 0) ?></strong></article>
            <article class="stat-card"><span class="stat-icon">✓</span><p>Selesai ditonton</p><strong><?= (int) ($stats['completed'] ?? 0) ?></strong></article>
        </section>
        <section class="content-card">
            <div class="section-heading"><div><span class="eyebrow">KOLEKSI TERBARU</span><h2>Anime di library-mu</h2></div><a class="text-link" href="library.php">Lihat semua →</a></div>
            <?php if (mysqli_num_rows($recent_anime) === 0): ?>
                <div class="empty-state"><span aria-hidden="true">✦</span><h3>Library-mu masih kosong</h3><p>Tambahkan anime pertama supaya gampang melanjutkan tontonanmu.</p><a class="button button-primary" href="library.php">Tambah anime</a></div>
            <?php else: ?>
                <div class="anime-list">
                    <?php while ($anime = mysqli_fetch_assoc($recent_anime)): ?>
                        <article class="anime-row"><span class="anime-cover" aria-hidden="true">✦</span><div class="anime-info"><h3><?= h($anime['title']) ?></h3><span class="status-pill"><?= h($anime['status']) ?></span></div><a class="row-link" href="library.php">Buka →</a></article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
