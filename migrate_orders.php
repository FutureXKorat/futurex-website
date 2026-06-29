<?php
/**
 * One-time migration: copies existing order JSON files -> MySQL orders table.
 * Visit this page once as admin after deploying, then delete the file.
 * Safe to run multiple times (INSERT IGNORE skips duplicates).
 */
declare(strict_types=1);
include __DIR__ . '/database.php';

// Admin-only
if (!isset($_SESSION['user_id'])) { http_response_code(403); die('Not logged in.'); }

$_mailCfgPath = __DIR__ . '/secure-config/futurex_mail.php';
$_mailCfg     = is_file($_mailCfgPath) ? require $_mailCfgPath : [];
$_adminEmail  = strtolower(trim((string)($_mailCfg['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'futurexkorat@gmail.com')));
$_stmt        = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
$_stmt->bind_param('i', $_SESSION['user_id']);
$_stmt->execute();
$_row = $_stmt->get_result()->fetch_assoc();
$_stmt->close();
if (strtolower(trim((string)($_row['email'] ?? ''))) !== $_adminEmail) {
    http_response_code(403); die('Admin only.');
}

header('Content-Type: text/plain; charset=utf-8');

$ordersDir = __DIR__ . '/storage/orders';
if (!is_dir($ordersDir)) {
    echo "No storage/orders directory. Nothing to migrate.\n"; exit;
}

$files = glob($ordersDir . '/*.json') ?: [];
echo "Found " . count($files) . " JSON order files.\n\n";

$ok = 0; $skip = 0; $err = 0;

foreach ($files as $file) {
    $json = @file_get_contents($file);
    if ($json === false) { echo "ERROR reading: $file\n"; $err++; continue; }

    $rec = json_decode($json, true);
    if (!is_array($rec) || !isset($rec['order_id'])) {
        echo "SKIP (invalid): " . basename($file) . "\n"; $skip++; continue;
    }

    $oid       = (string)$rec['order_id'];
    $uid       = (int)($rec['user_id'] ?? 0);
    $status    = (string)($rec['status'] ?? 'awaiting_review');

    try {
        $createdAt = (new DateTime((string)($rec['created_at'] ?? 'now')))->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $createdAt = date('Y-m-d H:i:s');
    }

    $updatedAt = null;
    if (!empty($rec['updated_at'])) {
        try { $updatedAt = (new DateTime((string)$rec['updated_at']))->format('Y-m-d H:i:s'); }
        catch (Exception $e) {}
    }

    $oidE      = $conn->real_escape_string($oid);
    $jsonE     = $conn->real_escape_string($json);
    $statusE   = $conn->real_escape_string($status);
    $createdE  = $conn->real_escape_string($createdAt);
    $updatedSql = $updatedAt ? "'" . $conn->real_escape_string($updatedAt) . "'" : 'NULL';

    $q = "INSERT IGNORE INTO `orders` (order_id, user_id, status, data, created_at, updated_at)
          VALUES ('$oidE', $uid, '$statusE', '$jsonE', '$createdE', $updatedSql)";

    if ($conn->query($q)) {
        if ($conn->affected_rows > 0) { echo "OK:   $oid\n"; $ok++; }
        else                          { echo "SKIP (exists): $oid\n"; $skip++; }
    } else {
        echo "ERROR: $oid — " . $conn->error . "\n"; $err++;
    }
}

echo "\n--- Done ---\n";
echo "Migrated : $ok\nSkipped  : $skip\nErrors   : $err\n";
echo "\nDELETE THIS FILE after confirming migration is complete.\n";
