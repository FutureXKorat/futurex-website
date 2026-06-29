<?php
declare(strict_types=1);

/**
 * Database connection file for Future X.
 * Loads credentials from /htdocs/secure-config/futurex_db.php
 */

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

if (session_status() === PHP_SESSION_NONE) {
    // Store sessions in MySQL so deploys don't log users out
    class DbSessionHandler implements SessionHandlerInterface {
        public function __construct(private mysqli $conn) {}
        public function open(string $path, string $name): bool { return true; }
        public function close(): bool { return true; }
        public function read(string $id): string|false {
            $expire = time() - (int)ini_get('session.gc_maxlifetime');
            $stmt   = $this->conn->prepare("SELECT data FROM sessions WHERE id = ? AND last_activity > ?");
            $stmt->bind_param("si", $id, $expire);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ? $row['data'] : '';
        }
        public function write(string $id, string $data): bool {
            $time = time();
            $stmt = $this->conn->prepare("REPLACE INTO sessions (id, data, last_activity) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $id, $data, $time);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        public function destroy(string $id): bool {
            $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->bind_param("s", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        public function gc(int $maxlifetime): int|false {
            $expire = time() - $maxlifetime;
            $stmt   = $this->conn->prepare("DELETE FROM sessions WHERE last_activity < ?");
            $stmt->bind_param("i", $expire);
            $stmt->execute();
            $count = $this->conn->affected_rows;
            $stmt->close();
            return $count;
        }
    }
    session_set_save_handler(new DbSessionHandler($conn));
    register_shutdown_function('session_write_close');
    session_start();
}

// Auto-logout when "remember me" session has passed midnight
if (isset($_SESSION['user_id'], $_SESSION['session_expires']) && time() >= $_SESSION['session_expires']) {
    $_SESSION = [];
    session_destroy();
}

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
