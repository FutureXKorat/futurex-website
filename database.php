<?php
declare(strict_types=1);

/**
 * Database connection file for Future X.
 * Loads credentials from /htdocs/secure-config/futurex_db.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-logout when "remember me" session has passed midnight
if (isset($_SESSION['user_id'], $_SESSION['session_expires']) && time() >= $_SESSION['session_expires']) {
    $_SESSION = [];
    session_destroy();
}

$cfgPath = __DIR__ . '/secure-config/futurex_db.php';

if (file_exists($cfgPath)) {
    $config = require $cfgPath;
} else {
    $config = [
        'DB_HOST'    => getenv('DB_HOST'),
        'DB_USER'    => getenv('DB_USER'),
        'DB_PASS'    => getenv('DB_PASS'),
        'DB_NAME'    => getenv('DB_NAME'),
        'DB_PORT'    => getenv('DB_PORT') ?: 3306,
        'DB_CHARSET' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ];
}

// Create the MySQL connection
$conn = @new mysqli(
    $config['DB_HOST'],
    $config['DB_USER'],
    $config['DB_PASS'],
    $config['DB_NAME'],
    (int)$config['DB_PORT']
);

// --- Language persistence via cookie (EN/TH) ---
$__allowedLangs = ['en', 'th'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $__allowedLangs, true)) {
    $__chosen = $_GET['lang'];
    $__oneYear = time() + 60 * 60 * 24 * 365;

    setcookie('lang', $__chosen, [
        'expires'  => $__oneYear,
        'path'     => '/',
        'secure'   => true,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);

    $lang = $__chosen; // use immediately
} else {
    $lang = (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $__allowedLangs, true))
        ? $_COOKIE['lang']
        : 'en'; // default
}

if ($conn->connect_error) {
    error_log('DB connection failed: ' . $conn->connect_error);
    die("Database connection failed.");
}

// Ensure UTF-8/emoji works
$charset = $config['DB_CHARSET'] ?? 'utf8mb4';
if (! $conn->set_charset($charset)) {
    // Fallback for hosts that ignore set_charset
    $conn->query("SET NAMES utf8mb4");
    $conn->query("SET CHARACTER SET utf8mb4");
}
