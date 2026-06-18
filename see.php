<?php
// /see.php — TEMP DEBUG. DELETE AFTER USE.
declare(strict_types=1);

// 1) Change this to any random string, e.g., from https://www.random.org/strings/
const DEBUG_KEY = '3ejGtsjDzy';

// 2) Require the key in query param: /see.php?key=CHANGE_ME_NOW_123
if (!isset($_GET['key']) || $_GET['key'] !== DEBUG_KEY) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Add ?key=DEBUG_KEY to access.\n";
    exit;
}

// 3) Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4) Prepare output
header('Content-Type: text/plain; charset=utf-8');

echo "== SERVER TIME ==\n";
echo date('c') . "\n\n";

echo "== SESSION ==\n";
if (empty($_SESSION)) {
    echo "(empty)\n";
} else {
    print_r($_SESSION);
}
echo "\n";

// 5) Quick guess keys you might use
$guess = [
    'username'     => $_SESSION['username']     ?? null,
    'name'         => $_SESSION['name']         ?? null,
    'user_name'    => $_SESSION['user_name']    ?? null,
    'display_name' => $_SESSION['display_name'] ?? null,
    'user_id'      => $_SESSION['user_id']      ?? null,
    'user_email'   => $_SESSION['user_email']   ?? ($_SESSION['email'] ?? null),
];
echo "== GUESSED USER FIELDS ==\n";
print_r($guess);
echo "\n";

// 6) Latest order JSON (if exists)
$ordersDir = __DIR__ . '/storage/orders';
if (is_dir($ordersDir)) {
    $files = array_values(array_filter(scandir($ordersDir) ?: [], fn($f) => preg_match('/\.json$/', (string)$f)));
    if ($files) {
        // sort by mtime desc
        usort($files, fn($a,$b) => filemtime("$ordersDir/$b") <=> filemtime("$ordersDir/$a"));
        $latest = $files[0];
        echo "== LATEST ORDER FILE ==\n";
        echo "$latest\n\n";
        $json = @file_get_contents("$ordersDir/$latest");
        echo "== LATEST ORDER CONTENT ==\n";
        echo ($json !== false ? $json : "(could not read)") . "\n\n";
    } else {
        echo "== LATEST ORDER FILE ==\n(none)\n\n";
    }
} else {
    echo "== ORDERS DIR ==\n($ordersDir not found)\n\n";
}

// 7) Basic request info
echo "== REQUEST INFO ==\n";
echo 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";
echo 'UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? '-') . "\n";
echo "OK\n";
