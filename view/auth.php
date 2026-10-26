<?php
require_once 'koneksi.php';

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
}

ensure_users_table($koneksi);

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function post_string($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
}

function require_login()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
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

function fetch_current_user($koneksi)
{
    $statement = mysqli_prepare($koneksi, 'SELECT id, nama, email, tanggal_lahir FROM users WHERE id = ?');
    mysqli_stmt_bind_param($statement, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    return $user;
}
