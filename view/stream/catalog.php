<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
ensure_stream_tables($koneksi);
$category = isset($catalog_category) && $catalog_category === 'movie' ? 'movie' : 'anime';
$page_title = $category === 'movie' ? 'Anime movie' : 'Jelajahi anime';
$query = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$query = substr($query, 0, 100);
if ($query === '') {
    $statement = mysqli_prepare($koneksi, 'SELECT id, title, description, synopsis, genre, cover_file, created_at FROM videos WHERE category = ? ORDER BY created_at DESC');
    mysqli_stmt_bind_param($statement, 's', $category);
} else {
    $search_term = '%' . $query . '%';
    $statement = mysqli_prepare($koneksi, 'SELECT id, title, description, synopsis, genre, cover_file, created_at FROM videos WHERE category = ? AND (title LIKE ? OR genre LIKE ?) ORDER BY created_at DESC');
    mysqli_stmt_bind_param($statement, 'sss', $category, $search_term, $search_term);
}
mysqli_stmt_execute($statement);
$videos = mysqli_stmt_get_result($statement);
$user = !empty($_SESSION['user_id']) ? fetch_current_user($koneksi) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> | MyWibu</title>
    <script src="../../js/theme.js"></script>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/auth.css">
</head>
<body class="app-page">
    <header class="app-header">
        <a class="brand" href="../index.php"><span class="brand-mark">✦</span> MyWibu</a>
        <nav class="app-nav" aria-label="Navigasi utama">
            <a href="../index.php">Beranda</a>
            <a class="<?= $category === 'anime' ? 'active' : '' ?>" href="anime.php">Anime</a>
            <a class="<?= $category === 'movie' ? 'active' : '' ?>" href="movies.php">Movies</a>
            <?php if ($user): ?><a href="../account/dashboard.php">Dashboard</a><?php endif; ?>
            <?php if ($user): ?><a href="../account/library.php">Library</a><?php endif; ?>
            <?php if ($user && $user['role'] === 'admin'): ?><a href="../admin/admin.php">Admin</a><?php endif; ?>
        </nav>
        <?php if ($user): ?>
            <div class="header-user"><span class="avatar"><?= h(strtoupper(substr($user['nama'], 0, 1))) ?></span><span><?= h($user['nama']) ?></span></div>
        <?php else: ?>
            <div class="public-actions"><a class="public-login" href="../auth/login.php">Masuk</a><a class="button button-primary" href="../auth/signup.php">Buat akun</a></div>
        <?php endif; ?>
    </header>
    <main class="app-main">
        <section class="welcome-banner catalog-banner">
            <div class="public-banner-copy">
                <span class="eyebrow">MYWIBU STREAMING</span>
                <h1><?= h($page_title) ?><span aria-hidden="true"> ✦</span></h1>
                <p>Pilih cerita, duduk santai, dan nikmati tontonan favoritmu.</p>
            </div>
            <div class="banner-decoration" aria-hidden="true">✧</div>
        </section>
        <section class="content-card catalog-content">
            <div class="section-heading">
                <div><span class="eyebrow"><?= $query !== '' ? 'HASIL PENCARIAN' : 'BARU DI MYWIBU' ?></span><h2><?= $query !== '' ? 'Hasil untuk “' . h($query) . '”' : 'Semua judul' ?></h2></div>
                <span class="count-badge"><?= mysqli_num_rows($videos) ?> judul</span>
            </div>
            <form class="catalog-search" method="get" role="search">
                <label class="visually-hidden" for="catalog-search">Cari judul atau genre</label>
                <input id="catalog-search" name="q" type="search" value="<?= h($query) ?>" maxlength="100" placeholder="Cari judul atau genre...">
                <button class="button button-primary" type="submit">Cari</button>
            </form>
            <?php if (mysqli_num_rows($videos) === 0): ?>
                <div class="empty-state"><span aria-hidden="true">✦</span><h3><?= $query !== '' ? 'Belum ada judul yang cocok' : 'Belum ada video di katalog' ?></h3><p><?= $query !== '' ? 'Coba gunakan kata kunci lain.' : 'Konten baru akan muncul di sini setelah admin menerbitkannya.' ?></p></div>
            <?php else: ?>
                <div class="stream-grid">
                    <?php while ($video = mysqli_fetch_assoc($videos)): ?>
                        <a class="stream-card" href="watch.php?id=<?= (int) $video['id'] ?>">
                            <div class="stream-poster">
                                <img src="<?= $video['cover_file'] ? '../../uploads/covers/' . rawurlencode(basename($video['cover_file'])) : '../../img/oshinoko.jpg' ?>" alt="">
                                <span class="play-badge" aria-hidden="true">▶</span>
                                <span class="category-badge"><?= h($video['genre'] ?: ucfirst($category)) ?></span>
                            </div>
                            <div class="stream-card-copy">
                                <h3><?= h($video['title']) ?></h3>
                                <p><?= h($video['description']) ?></p>
                                <span class="text-link">Tonton sekarang →</span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
