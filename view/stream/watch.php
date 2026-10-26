<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
ensure_stream_tables($koneksi);
$video_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$video_id) {
    http_response_code(404);
    exit('Video tidak ditemukan.');
}

$statement = mysqli_prepare($koneksi, 'SELECT videos.*, users.nama AS uploader FROM videos JOIN users ON users.id = videos.uploaded_by WHERE videos.id = ?');
mysqli_stmt_bind_param($statement, 'i', $video_id);
mysqli_stmt_execute($statement);
$video = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
mysqli_stmt_close($statement);

if (!$video) {
    http_response_code(404);
    exit('Video tidak ditemukan.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    $body = trim(post_string('body'));
    if (!verify_csrf_token()) {
        $error = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif ($body === '' || strlen($body) > 1000) {
        $error = 'Komentar wajib diisi dan maksimal 1.000 karakter.';
    } else {
        $statement = mysqli_prepare($koneksi, 'INSERT INTO comments (video_id, user_id, body) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($statement, 'iis', $video_id, $_SESSION['user_id'], $body);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
        header('Location: watch.php?id=' . $video_id . '#comments');
        exit;
    }
}

$statement = mysqli_prepare($koneksi, 'SELECT comments.body, comments.created_at, users.nama FROM comments JOIN users ON users.id = comments.user_id WHERE comments.video_id = ? ORDER BY comments.created_at DESC LIMIT 100');
mysqli_stmt_bind_param($statement, 'i', $video_id);
mysqli_stmt_execute($statement);
$comments = mysqli_stmt_get_result($statement);
$user = !empty($_SESSION['user_id']) ? fetch_current_user($koneksi) : null;

$category = $video['category'] === 'movie' ? 'movie' : 'anime';
$video_mime_type = pathinfo($video['video_file'], PATHINFO_EXTENSION) === 'webm' ? 'video/webm' : 'video/mp4';
$statement = mysqli_prepare($koneksi, 'SELECT id, title, cover_file FROM videos WHERE category = ? AND id <> ? ORDER BY created_at DESC LIMIT 4');
mysqli_stmt_bind_param($statement, 'si', $category, $video_id);
mysqli_stmt_execute($statement);
$more_videos = mysqli_stmt_get_result($statement);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($video['title']) ?> | MyWibu</title>
    <script src="../../js/theme.js"></script>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/auth.css">
</head>
<body class="app-page">
    <header class="app-header">
        <a class="brand" href="../index.php"><span class="brand-mark">✦</span> MyWibu</a>
        <nav class="app-nav" aria-label="Navigasi utama">
            <a href="../index.php">Beranda</a>
            <a href="anime.php">Anime</a>
            <a href="movies.php">Movies</a>
            <?php if ($user): ?><a href="../account/dashboard.php">Dashboard</a><?php endif; ?>
            <?php if ($user): ?><a href="../account/library.php">Library</a><?php endif; ?>
            <?php if ($user && $user['role'] === 'admin'): ?><a href="../admin/admin.php">Admin</a><?php endif; ?>
        </nav>
        <?php if ($user): ?>
            <div class="header-user"><span class="avatar"><?= h(strtoupper(substr($user['nama'], 0, 1))) ?></span><span><?= h($user['nama']) ?></span></div>
        <?php else: ?>
            <div class="public-actions"><a class="public-login" href="../auth/login.php">Masuk</a><a class="button button-primary" href="../auth/signup.php">Daftar</a></div>
        <?php endif; ?>
    </header>
    <main class="app-main watch-main">
        <section class="content-card player-card">
            <video class="video-player" controls preload="metadata" playsinline poster="<?= $video['cover_file'] ? '../../uploads/covers/' . rawurlencode(basename($video['cover_file'])) : '' ?>">
                <source src="../../uploads/videos/<?= rawurlencode(basename($video['video_file'])) ?>" type="<?= h($video_mime_type) ?>">
                Browser kamu tidak mendukung pemutar video.
            </video>
            <div class="watch-details">
                <span class="eyebrow"><?= h(strtoupper($category)) ?><?= $video['genre'] ? ' · ' . h($video['genre']) : '' ?></span>
                <h1><?= h($video['title']) ?></h1>
                <p class="watch-meta">Diunggah oleh <?= h($video['uploader']) ?> · <?= h(date('d M Y', strtotime($video['created_at']))) ?></p>
                <h2>Sinopsis</h2>
                <p class="watch-synopsis"><?= nl2br(h($video['synopsis'])) ?></p>
                <details class="description-details"><summary>Tentang video</summary><p><?= nl2br(h($video['description'])) ?></p></details>
            </div>
        </section>

        <section class="content-card comments-card" id="comments">
            <div class="section-heading"><div><span class="eyebrow">DISKUSI</span><h2>Komentar</h2></div><span class="count-badge"><?= mysqli_num_rows($comments) ?> komentar</span></div>
            <?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
            <?php if ($user): ?>
                <form method="post" class="comment-form">
                    <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                    <label for="comment-body">Tulis komentar sebagai <?= h($user['nama']) ?></label>
                    <textarea id="comment-body" name="body" maxlength="1000" rows="3" required placeholder="Bagikan pendapatmu tentang anime ini..."><?= h(post_string('body')) ?></textarea>
                    <button class="button button-primary" type="submit">Kirim komentar <span aria-hidden="true">→</span></button>
                </form>
            <?php else: ?>
                <p class="comment-login">Ingin ikut berdiskusi? <a href="../auth/login.php">Masuk</a> atau <a href="../auth/signup.php">buat akun</a>.</p>
            <?php endif; ?>
            <div class="comment-list">
                <?php if (mysqli_num_rows($comments) === 0): ?>
                    <div class="empty-state"><span aria-hidden="true">♡</span><h3>Jadilah yang pertama berkomentar</h3><p>Mulai obrolan dengan komunitas MyWibu.</p></div>
                <?php else: ?>
                    <?php while ($comment = mysqli_fetch_assoc($comments)): ?>
                        <article class="comment-item"><span class="avatar"><?= h(strtoupper(substr($comment['nama'], 0, 1))) ?></span><div><h3><?= h($comment['nama']) ?><time><?= h(date('d M Y, H:i', strtotime($comment['created_at']))) ?></time></h3><p><?= nl2br(h($comment['body'])) ?></p></div></article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php if (mysqli_num_rows($more_videos) > 0): ?>
            <section class="content-card">
                <div class="section-heading"><div><span class="eyebrow">LANJUT MENJELAJAH</span><h2>Video lainnya</h2></div></div>
                <div class="public-feature-grid">
                    <?php while ($related = mysqli_fetch_assoc($more_videos)): ?>
                        <a class="public-feature-card" href="watch.php?id=<?= (int) $related['id'] ?>">
                            <img src="<?= $related['cover_file'] ? '../../uploads/covers/' . rawurlencode(basename($related['cover_file'])) : '../../img/oshinoko.jpg' ?>" alt="">
                            <h3><?= h($related['title']) ?></h3><span class="text-link">Tonton →</span>
                        </a>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
