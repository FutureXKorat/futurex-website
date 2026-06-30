<?php
/**
 * Shared admin auth gate. Include right after database.php.
 * Sets $isSuperAdmin and $isEmployeeAdmin, then redirects to /admin/login.php if neither.
 */

$_mailCfgPath = dirname(__DIR__) . '/secure-config/futurex_mail.php';
$_mailCfg     = is_file($_mailCfgPath) ? require $_mailCfgPath : [];
$ADMIN_EMAIL  = strtolower(trim((string)($_mailCfg['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'futurexkorat@gmail.com')));

$isSuperAdmin    = false;
$isEmployeeAdmin = false;

// Employee-admin: logged in via admins table
if (isset($_SESSION['admin_id'])) {
    $_s = $conn->prepare("SELECT id FROM admins WHERE id = ? LIMIT 1");
    $_s->bind_param('i', $_SESSION['admin_id']);
    $_s->execute();
    $_r = $_s->get_result()->fetch_assoc();
    $_s->close();
    if ($_r) {
        $isEmployeeAdmin = true;
    } else {
        unset($_SESSION['admin_id']); // stale session — wipe it
    }
}

// Super-admin: regular user whose email matches ADMIN_EMAIL
if (!$isEmployeeAdmin && isset($_SESSION['user_id'])) {
    $_s = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
    $_s->bind_param('i', $_SESSION['user_id']);
    $_s->execute();
    $_r = $_s->get_result()->fetch_assoc();
    $_s->close();
    if ($_r && strtolower(trim((string)$_r['email'])) === $ADMIN_EMAIL) {
        $isSuperAdmin = true;
    }
}

if (!$isSuperAdmin && !$isEmployeeAdmin) {
    header('Location: /admin/login.php'); exit;
}
