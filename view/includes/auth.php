<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function ensure_users_table($koneksi)
{
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(40) NOT NULL UNIQUE,
        email VARCHAR(254) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        tanggal_lahir DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($koneksi, $sql)) {
        throw new RuntimeException('Gagal menyiapkan tabel akun: ' . mysqli_error($koneksi));
    }

    $role_column = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'role'");
    if (!$role_column) {
        throw new RuntimeException('Gagal memeriksa peran pengguna: ' . mysqli_error($koneksi));
    }
    if (mysqli_num_rows($role_column) === 0 && !mysqli_query($koneksi, "ALTER TABLE users ADD role VARCHAR(20) NOT NULL DEFAULT 'member'")) {
        throw new RuntimeException('Gagal menambahkan peran pengguna: ' . mysqli_error($koneksi));
    }
}

ensure_users_table($koneksi);

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function view_url($path)
{
    $script_directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (in_array(basename($script_directory), ['account', 'admin', 'auth', 'includes', 'stream'], true)) {
        $script_directory = dirname($script_directory);
    }

    return rtrim($script_directory, '/') . '/' . ltrim($path, '/');
}

function post_string($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
}

function require_login()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . view_url('auth/login.php'));
        exit;
    }
}

function require_admin($koneksi)
{
    require_login();

    $statement = mysqli_prepare($koneksi, 'SELECT role FROM users WHERE id = ?');
    mysqli_stmt_bind_param($statement, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses hanya tersedia untuk admin.');
    }
}

function create_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token()
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function ensure_library_table($koneksi)
{
    $sql = "CREATE TABLE IF NOT EXISTS anime_library (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(160) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Plan to Watch',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX user_library (user_id, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($koneksi, $sql)) {
        throw new RuntimeException('Gagal menyiapkan library anime: ' . mysqli_error($koneksi));
    }
}

function ensure_stream_tables($koneksi)
{
    $user_id_column = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'id'");
    if (!$user_id_column) {
        throw new RuntimeException('Gagal memeriksa kolom ID pengguna: ' . mysqli_error($koneksi));
    }

    $user_id_definition = mysqli_fetch_assoc($user_id_column);
    if (!$user_id_definition || !preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)(?:\(\d+\))?(?:\s+(unsigned))?$/i', $user_id_definition['Type'], $matches)) {
        throw new RuntimeException('Tipe kolom ID pengguna tidak didukung untuk tabel komentar.');
    }

    $user_id_type = strtolower($matches[1]) === 'integer' ? 'INT' : strtoupper($matches[1]);
    if (isset($matches[2]) && strtolower($matches[2]) === 'unsigned') {
        $user_id_type .= ' UNSIGNED';
    }

    $tables = [
        "CREATE TABLE IF NOT EXISTS videos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            description TEXT NOT NULL,
            synopsis TEXT NOT NULL,
            category VARCHAR(20) NOT NULL DEFAULT 'anime',
            genre VARCHAR(100) NOT NULL DEFAULT '',
            video_file VARCHAR(80) NOT NULL,
            cover_file VARCHAR(80) DEFAULT NULL,
            uploaded_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX video_category_date (category, created_at),
            INDEX video_uploaded_by (uploaded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS comments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            video_id INT UNSIGNED NOT NULL,
            user_id {$user_id_type} NOT NULL,
            body VARCHAR(1000) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX comments_video_date (video_id, created_at),
            CONSTRAINT comments_video_fk FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
            CONSTRAINT comments_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $sql) {
        if (!mysqli_query($koneksi, $sql)) {
            throw new RuntimeException('Gagal menyiapkan tabel streaming: ' . mysqli_error($koneksi));
        }
    }
}

function fetch_current_user($koneksi)
{
    $statement = mysqli_prepare($koneksi, 'SELECT id, nama, email, tanggal_lahir, role FROM users WHERE id = ?');
    mysqli_stmt_bind_param($statement, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    if (!$user) {
        session_destroy();
        header('Location: ' . view_url('auth/login.php'));
        exit;
    }

    return $user;
}
