<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
ensure_stream_tables($koneksi);

$is_logged_in = !empty($_SESSION['user_id']);
$display_name = $is_logged_in && !empty($_SESSION['nama']) ? $_SESSION['nama'] : '';
$current_user = $is_logged_in ? fetch_current_user($koneksi) : null;
$is_admin = $current_user && $current_user['role'] === 'admin';
$latest_videos = mysqli_query($koneksi, 'SELECT id, title, description, category, genre, cover_file FROM videos ORDER BY created_at DESC LIMIT 6');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f7fb">
    <title>MyWibu | Rumah untuk cerita anime-mu</title>
    <script src="../js/theme.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="app-page">
    <header class="app-header">
        <a class="brand" href="index.php"><span class="brand-mark">✦</span> MyWibu</a>
        <nav class="app-nav" aria-label="Navigasi utama">
            <a class="active" href="index.php">Beranda</a>
            <a href="stream/anime.php">Anime</a>
            <a href="stream/movies.php">Movies</a>
            <?php if ($is_logged_in): ?>
            <a href="account/dashboard.php">Dashboard</a>
            <a href="account/library.php">Library</a>
            <a href="account/profile.php">Profil</a>
            <?php endif; ?>
            <?php if ($is_admin): ?><a href="admin/admin.php">Admin</a><?php endif; ?>
        </nav>
        <?php if ($is_logged_in): ?>
            <div class="header-user">
                <span class="avatar"><?= h(strtoupper(substr($display_name, 0, 1))) ?></span>
                <span><?= h($display_name) ?></span>
                <form method="post" action="auth/logout.php">
                    <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                    <button class="logout-link" type="submit">Keluar</button>
                </form>
            </div>
        <?php else: ?>
            <div class="public-actions">
                <a class="public-login" href="auth/login.php">Masuk</a>
                <a class="button button-primary" href="auth/signup.php">Buat akun <span aria-hidden="true">→</span></a>
            </div>
        <?php endif; ?>
    </header>

    <main class="app-main public-main">
        <section class="welcome-banner public-banner">
            <div class="public-banner-copy">
                <span class="eyebrow"><?= $is_logged_in ? 'SELAMAT DATANG KEMBALI' : 'SELAMAT DATANG DI MYWIBU' ?></span>
                <h1><?php if ($is_logged_in): ?>Halo, <?= h($display_name) ?> <span aria-hidden="true">✦</span><?php else: ?>Semua cerita anime-mu,<br><span>satu tempat.</span><?php endif; ?></h1>
                <p><?= $is_logged_in ? 'Lanjutkan menonton rilisan terbaru dan berbagi pendapat dengan komunitas.' : 'Temukan anime dan movie terbaru, tonton langsung, lalu bagikan pendapatmu.' ?></p>
                <a class="button button-light" href="<?= $is_logged_in ? 'stream/anime.php' : 'auth/signup.php' ?>"><?= $is_logged_in ? 'Jelajahi katalog' : 'Mulai sekarang' ?> <span aria-hidden="true">→</span></a>
            </div>
            <div class="banner-decoration" aria-hidden="true">✧</div>
        </section>

        <section class="stats-grid public-highlights" aria-label="Fitur MyWibu">
            <article class="stat-card public-highlight">
                <span class="stat-icon" aria-hidden="true">▤</span>
                <div><p>Katalog streaming</p><strong>Tonton anime</strong><span>Jelajahi anime dan movie yang tersedia di MyWibu.</span></div>
            </article>
            <article class="stat-card public-highlight">
                <span class="stat-icon" aria-hidden="true">▶</span>
                <div><p>Rilisan terbaru</p><strong>Selalu update</strong><span>Temukan video terbaru yang diunggah admin.</span></div>
            </article>
            <article class="stat-card public-highlight">
                <span class="stat-icon" aria-hidden="true">♡</span>
                <div><p>Ruang komunitas</p><strong>Komentar</strong><span>Masuk untuk berbagi kesan dan berdiskusi setelah menonton.</span></div>
            </article>
        </section>

        <section class="content-card public-feature">
            <div class="section-heading">
                <div><span class="eyebrow">RILISAN TERBARU</span><h2>Tontonan baru untukmu</h2></div>
                <a class="text-link" href="stream/anime.php">Jelajahi katalog →</a>
            </div>
            <?php if (mysqli_num_rows($latest_videos) === 0): ?>
                <div class="empty-state"><span aria-hidden="true">▶</span><h3>Belum ada tontonan</h3><p>Anime dan movie terbaru akan tampil di sini setelah admin menerbitkannya.</p><?php if (!$is_admin): ?><a class="button button-primary" href="stream/anime.php">Jelajahi katalog</a><?php endif; ?></div>
            <?php else: ?>
                <div class="stream-grid stream-grid-home">
                    <?php while ($video = mysqli_fetch_assoc($latest_videos)): ?>
                        <a class="stream-card" href="stream/watch.php?id=<?= (int) $video['id'] ?>">
                            <div class="stream-poster">
                                <img src="<?= $video['cover_file'] ? '../uploads/covers/' . rawurlencode(basename($video['cover_file'])) : '../img/oshinoko.jpg' ?>" alt="">
                                <span class="play-badge" aria-hidden="true">▶</span>
                                <span class="category-badge"><?= h($video['genre'] ?: ucfirst($video['category'])) ?></span>
                            </div>
                            <div class="stream-card-copy"><h3><?= h($video['title']) ?></h3><p><?= h($video['description']) ?></p><span class="text-link">Tonton sekarang →</span></div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>
        <footer class="public-footer"><a class="brand" href="index.php"><span class="brand-mark">✦</span> MyWibu</a><span>Make your list, make your world.</span></footer>
    </main>
</body>
</html>
