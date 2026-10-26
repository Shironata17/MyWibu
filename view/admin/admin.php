<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
require_admin($koneksi);
ensure_stream_tables($koneksi);
$user = fetch_current_user($koneksi);
$error = '';
$project_root = dirname(__DIR__, 2);
$video_directory = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos';
$cover_directory = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'covers';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif (isset($_POST['publish_video'])) {
        $title = trim(post_string('title'));
        $description = trim(post_string('description'));
        $synopsis = trim(post_string('synopsis'));
        $genre = trim(post_string('genre'));
        $category = post_string('category');
        $rights_confirmed = post_string('rights_confirmation') === 'yes';
        $video = $_FILES['video'] ?? null;
        $cover = $_FILES['cover'] ?? null;

        if (!$rights_confirmed) {
            $error = 'Konfirmasi bahwa kamu memiliki hak untuk mendistribusikan video ini.';
        } elseif ($title === '' || strlen($title) > 180 || $description === '' || strlen($description) > 5000 || $synopsis === '' || strlen($synopsis) > 5000) {
            $error = 'Judul, deskripsi, dan sinopsis wajib diisi. Deskripsi dan sinopsis maksimal 5.000 karakter.';
        } elseif (strlen($genre) > 100 || !in_array($category, ['anime', 'movie'], true)) {
            $error = 'Kategori atau genre tidak valid.';
        } elseif (!$video || !isset($video['error']) || $video['error'] !== UPLOAD_ERR_OK) {
            $error = 'Pilih file video MP4 atau WebM yang valid (maksimal 200 MB).';
        } elseif ($video['size'] > 200 * 1024 * 1024) {
            $error = 'Ukuran video melebihi batas 200 MB.';
        } elseif (!is_uploaded_file($video['tmp_name'])) {
            $error = 'File video yang dikirim tidak valid.';
        } else {
            $video_mime = (new finfo(FILEINFO_MIME_TYPE))->file($video['tmp_name']);
            $video_types = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
            $cover_mime = null;
            $cover_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (!isset($video_types[$video_mime])) {
                $error = 'Format video tidak didukung. Gunakan MP4 atau WebM.';
            } elseif ($cover && $cover['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($cover['error'] !== UPLOAD_ERR_OK || $cover['size'] > 5 * 1024 * 1024 || !is_uploaded_file($cover['tmp_name'])) {
                    $error = 'Cover harus berupa JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.';
                } else {
                    $cover_mime = (new finfo(FILEINFO_MIME_TYPE))->file($cover['tmp_name']);
                    if (!isset($cover_types[$cover_mime])) {
                        $error = 'Format cover tidak didukung. Gunakan JPG, PNG, atau WebP.';
                    }
                }
            }

            if ($error === '') {
                foreach ([$video_directory, $cover_directory] as $directory) {
                    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                        throw new RuntimeException('Gagal menyiapkan folder media: ' . $directory);
                    }
                }

                $video_name = bin2hex(random_bytes(16)) . '.' . $video_types[$video_mime];
                $video_path = $video_directory . DIRECTORY_SEPARATOR . $video_name;
                if (!move_uploaded_file($video['tmp_name'], $video_path)) {
                    throw new RuntimeException('Video gagal disimpan. Periksa izin folder uploads.');
                }

                $cover_name = null;
                if ($cover_mime !== null) {
                    $cover_name = bin2hex(random_bytes(16)) . '.' . $cover_types[$cover_mime];
                    if (!move_uploaded_file($cover['tmp_name'], $cover_directory . DIRECTORY_SEPARATOR . $cover_name)) {
                        unlink($video_path);
                        throw new RuntimeException('Cover gagal disimpan. Periksa izin folder uploads.');
                    }
                }

                try {
                    $statement = mysqli_prepare($koneksi, 'INSERT INTO videos (title, description, synopsis, category, genre, video_file, cover_file, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param($statement, 'sssssssi', $title, $description, $synopsis, $category, $genre, $video_name, $cover_name, $_SESSION['user_id']);
                    mysqli_stmt_execute($statement);
                    mysqli_stmt_close($statement);
                } catch (mysqli_sql_exception $exception) {
                    unlink($video_path);
                    if ($cover_name !== null) {
                        unlink($cover_directory . DIRECTORY_SEPARATOR . $cover_name);
                    }
                    throw $exception;
                }
                header('Location: admin.php?published=1');
                exit;
            }
        }
    } elseif (isset($_POST['delete_video'])) {
        $video_id = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);
        if (!$video_id) {
            $error = 'Video yang dipilih tidak valid.';
        } else {
            $statement = mysqli_prepare($koneksi, 'SELECT video_file, cover_file FROM videos WHERE id = ?');
            mysqli_stmt_bind_param($statement, 'i', $video_id);
            mysqli_stmt_execute($statement);
            $video_result = mysqli_stmt_get_result($statement);
            $video = mysqli_fetch_assoc($video_result);
            mysqli_stmt_close($statement);

            if ($video) {
                $statement = mysqli_prepare($koneksi, 'DELETE FROM videos WHERE id = ?');
                mysqli_stmt_bind_param($statement, 'i', $video_id);
                mysqli_stmt_execute($statement);
                mysqli_stmt_close($statement);
                $video_path = $video_directory . DIRECTORY_SEPARATOR . basename($video['video_file']);
                if (is_file($video_path) && !unlink($video_path)) {
                    throw new RuntimeException('Video dihapus dari katalog tetapi file video gagal dihapus.');
                }
                if ($video['cover_file']) {
                    $cover_path = $cover_directory . DIRECTORY_SEPARATOR . basename($video['cover_file']);
                    if (is_file($cover_path) && !unlink($cover_path)) {
                        throw new RuntimeException('Video dihapus dari katalog tetapi file cover gagal dihapus.');
                    }
                }
            }

            header('Location: admin.php?removed=1');
            exit;
        }
    }
}

$videos = mysqli_query($koneksi, 'SELECT videos.id, videos.title, videos.category, videos.created_at, users.nama AS uploader FROM videos JOIN users ON users.id = videos.uploaded_by ORDER BY videos.created_at DESC');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | MyWibu</title>
    <script src="../../js/theme.js"></script>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/auth.css">
</head>
<body class="app-page">
    <header class="app-header">
        <a class="brand" href="../index.php"><span class="brand-mark">✦</span> MyWibu</a>
        <nav class="app-nav" aria-label="Navigasi utama">
            <a href="../index.php">Beranda</a>
            <a href="../account/dashboard.php">Dashboard</a>
            <a class="active" href="admin.php">Admin</a>
        </nav>
        <div class="header-user"><span class="avatar"><?= h(strtoupper(substr($user['nama'], 0, 1))) ?></span><span><?= h($user['nama']) ?></span><form method="post" action="../auth/logout.php"><input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>"><button class="logout-link" type="submit">Keluar</button></form></div>
    </header>
    <main class="app-main">
        <section class="page-heading"><div><span class="eyebrow">PENGELOLAAN KONTEN</span><h1>Admin studio</h1><p>Terbitkan judul baru untuk ditonton komunitas MyWibu.</p></div></section>
        <?php if (isset($_GET['published'])): ?><div class="notice notice-success" role="status">Video berhasil diterbitkan.</div><?php endif; ?>
        <?php if (isset($_GET['removed'])): ?><div class="notice notice-success" role="status">Video berhasil dihapus.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <section class="content-card">
            <div class="section-heading"><div><span class="eyebrow">UPLOAD BARU</span><h2>Detail tontonan</h2></div></div>
            <form method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>">
                <label for="title">Judul</label>
                <input id="title" name="title" type="text" maxlength="180" required value="<?= h(post_string('title')) ?>" placeholder="Contoh: Petualangan baru">
                <div class="admin-form-row">
                    <div><label for="category">Kategori</label><select id="category" name="category"><option value="anime">Anime</option><option value="movie">Anime movie</option></select></div>
                    <div><label for="genre">Genre</label><input id="genre" name="genre" type="text" maxlength="100" value="<?= h(post_string('genre')) ?>" placeholder="Action, drama, fantasy..."></div>
                </div>
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" maxlength="5000" rows="3" required placeholder="Info singkat yang tampil di katalog"><?= h(post_string('description')) ?></textarea>
                <label for="synopsis">Sinopsis</label>
                <textarea id="synopsis" name="synopsis" maxlength="5000" rows="5" required placeholder="Ceritakan premis dan gambaran kisahnya"><?= h(post_string('synopsis')) ?></textarea>
                <div class="admin-form-row">
                    <div><label for="video">File video <span class="label-hint">MP4/WebM · maks. 200 MB</span></label><input id="video" name="video" type="file" accept="video/mp4,video/webm" required></div>
                    <div><label for="cover">Poster / cover <span class="label-hint">opsional · maks. 5 MB</span></label><input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp"></div>
                </div>
                <label class="rights-confirmation"><input type="checkbox" name="rights_confirmation" value="yes" required> Saya memiliki hak atau izin untuk mengunggah dan mendistribusikan video ini.</label>
                <button class="button button-primary" type="submit" name="publish_video">Terbitkan video <span aria-hidden="true">↑</span></button>
            </form>
        </section>
        <section class="content-card">
            <div class="section-heading"><div><span class="eyebrow">KATALOG KAMU</span><h2>Video terbit</h2></div><span class="count-badge"><?= mysqli_num_rows($videos) ?> judul</span></div>
            <?php if (mysqli_num_rows($videos) === 0): ?>
                <div class="empty-state"><span aria-hidden="true">✦</span><h3>Belum ada video</h3><p>Video yang diterbitkan akan muncul di sini dan di katalog publik.</p></div>
            <?php else: ?>
                <div class="anime-list">
                    <?php while ($video = mysqli_fetch_assoc($videos)): ?>
                        <article class="anime-row"><span class="anime-cover" aria-hidden="true">▶</span><div class="anime-info"><h3><?= h($video['title']) ?></h3><span class="status-pill"><?= h(ucfirst($video['category'])) ?> · oleh <?= h($video['uploader']) ?></span></div><a class="row-link" href="../stream/watch.php?id=<?= (int) $video['id'] ?>">Lihat →</a><form method="post" onsubmit="return confirm('Hapus video ini dari katalog?')"><input type="hidden" name="csrf_token" value="<?= h(create_csrf_token()) ?>"><input type="hidden" name="video_id" value="<?= (int) $video['id'] ?>"><button class="button button-small button-danger" type="submit" name="delete_video">Hapus</button></form></article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
