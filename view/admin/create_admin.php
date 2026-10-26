<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';

$username = getenv('MYWIBU_ADMIN_USERNAME') ?: '';
$email = getenv('MYWIBU_ADMIN_EMAIL') ?: '';
$password = getenv('MYWIBU_ADMIN_PASSWORD') ?: '';
$birth_date = getenv('MYWIBU_ADMIN_BIRTH_DATE') ?: '';
$parsed_birth_date = DateTime::createFromFormat('!Y-m-d', $birth_date);

if (strlen($username) < 3 || strlen($username) > 40 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !$parsed_birth_date || $parsed_birth_date->format('Y-m-d') !== $birth_date) {
    fwrite(STDERR, "Set MYWIBU_ADMIN_USERNAME (3-40 chars), MYWIBU_ADMIN_EMAIL, MYWIBU_ADMIN_BIRTH_DATE (YYYY-MM-DD), and MYWIBU_ADMIN_PASSWORD (at least 12 chars) before running this script.\n");
    exit(1);
}

$statement = mysqli_prepare($koneksi, 'SELECT id FROM users WHERE nama = ? OR email = ? LIMIT 1');
mysqli_stmt_bind_param($statement, 'ss', $username, $email);
mysqli_stmt_execute($statement);
$existing = mysqli_stmt_get_result($statement);
$is_taken = mysqli_num_rows($existing) > 0;
mysqli_stmt_close($statement);

if ($is_taken) {
    fwrite(STDERR, "That username or email is already registered. No account was changed.\n");
    exit(1);
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';
$statement = mysqli_prepare($koneksi, 'INSERT INTO users (nama, email, password, tanggal_lahir, role) VALUES (?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($statement, 'sssss', $username, $email, $password_hash, $birth_date, $role);
mysqli_stmt_execute($statement);
mysqli_stmt_close($statement);

fwrite(STDOUT, "Admin account created for {$username}.\n");
