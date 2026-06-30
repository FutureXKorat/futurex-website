<?php
declare(strict_types=1);

/**
 * Database connection file for Future X.
 * Loads credentials from /htdocs/secure-config/futurex_db.php
 */

// --- DB must come first so the session handler can use it ---
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

// --- MySQL-backed sessions so users stay logged in across Railway redeploys ---
// Sessions are stored in the DB, not in the container filesystem, so a new deploy
// doesn't wipe them. The 12 AM expiry below still works exactly as before.
$conn->query("CREATE TABLE IF NOT EXISTS `sessions` (
  `id`          VARCHAR(128)  NOT NULL,
  `data`        MEDIUMTEXT    NOT NULL,
  `last_access` INT UNSIGNED  NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
// Add last_access if the table pre-existed without it
$conn->query("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `last_access` INT UNSIGNED NOT NULL DEFAULT 0");

class MySQLSessionHandler implements SessionHandlerInterface {
    private mysqli $conn;
    public function __construct(mysqli $conn) { $this->conn = $conn; }
    public function open(string $savePath, string $sessionName): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false {
        $stmt = $this->conn->prepare("SELECT `data` FROM `sessions` WHERE `id` = ?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->bind_result($data);
        $result = $stmt->fetch() ? $data : '';
        $stmt->close();
        return $result;
    }
    public function write(string $id, string $data): bool {
        $t = time();
        $stmt = $this->conn->prepare("REPLACE INTO `sessions` (`id`, `data`, `last_access`) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $id, $data, $t);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    public function destroy(string $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM `sessions` WHERE `id` = ?");
        $stmt->bind_param('s', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    public function gc(int $maxlifetime): int|false {
        // Keep sessions alive for 25 hours so overnight logins survive until midnight
        $oldest = time() - 90000;
        $stmt = $this->conn->prepare("DELETE FROM `sessions` WHERE `last_access` < ?");
        $stmt->bind_param('i', $oldest);
        $stmt->execute();
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $__sessionHandler = new MySQLSessionHandler($conn);
    session_set_save_handler($__sessionHandler, true);
    session_start();
}

// Auto-logout when "remember me" session has passed midnight
if (isset($_SESSION['user_id'], $_SESSION['session_expires']) && time() >= $_SESSION['session_expires']) {
    $_SESSION = [];
    session_destroy();
}

// Ensure orders table exists (fast no-op once created)
$conn->query("CREATE TABLE IF NOT EXISTS `orders` (
  `order_id`   VARCHAR(60)  NOT NULL,
  `user_id`    INT          NOT NULL DEFAULT 0,
  `status`     VARCHAR(30)  NOT NULL DEFAULT 'awaiting_review',
  `data`       MEDIUMTEXT   NOT NULL,
  `created_at` DATETIME     NOT NULL,
  `updated_at` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `idx_user_id`  (`user_id`),
  KEY `idx_status`   (`status`),
  KEY `idx_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
