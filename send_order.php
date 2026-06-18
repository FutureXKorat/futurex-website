<?php
// file: send_order.php
declare(strict_types=1);
ini_set('display_errors', '1'); error_reporting(E_ALL);
// before: session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- PHPMailer ----------
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// ---------- Config ----------
$cfgPath = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/').'/secure-config/futurex_mail.php';
$mailCfg = is_file($cfgPath) ? require $cfgPath : [];

$ADMIN_EMAIL = (string)($mailCfg['ADMIN_EMAIL'] ?? 'futurexkorat@gmail.com');
$FROM_EMAIL  = (string)($mailCfg['FROM_EMAIL']  ?? 'futurexkorat@gmail.com');
$FROM_NAME   = (string)($mailCfg['FROM_NAME_ORDER']   ?? 'Future X Order');
$SMTP_HOST   = (string)($mailCfg['SMTP_HOST']   ?? '');
$SMTP_USER   = (string)($mailCfg['SMTP_USER']   ?? '');
$SMTP_PASS   = (string)($mailCfg['SMTP_PASS']   ?? '');
$SMTP_PORT   = (int)   ($mailCfg['SMTP_PORT']   ?? 587);
$SMTP_SECURE = (string)($mailCfg['SMTP_SECURE'] ?? 'tls');

function __email_local_part(?string $email): ?string {
    $e = trim((string)$email);
    if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL)) return null;
    $lp = strstr($e, '@', true);
    return ($lp !== false && $lp !== '') ? $lp : null;
}

function __db_pdo(): ?PDO {
    $cfgPath = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/').'/secure-config/db.php';
    $dbcfg = is_file($cfgPath) ? require $cfgPath : [];
    $dsn  = getenv('DB_DSN')  ?: ($dbcfg['dsn']  ?? null);
    $user = getenv('DB_USER') ?: ($dbcfg['user'] ?? null);
    $pass = getenv('DB_PASS') ?? ($dbcfg['pass'] ?? '');
    if (!$dsn || !$user) return null;
    try {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        return null;
    }
}

function __db_username_by_id(int $userId): ?string {
    $pdo = __db_pdo();
    if (!$pdo) return null;
    $cols = ['username','name','display_name','login','handle','email'];
    foreach ($cols as $c) {
        try {
            $stmt = $pdo->prepare("SELECT $c AS v FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch();
            if ($row && isset($row['v']) && $row['v'] !== null) {
                $val = trim((string)$row['v']);
                if ($val !== '') {
                    if ($c === 'email') {
                        $lp = __email_local_part($val);
                        if ($lp) return $lp;
                    } else {
                        return $val;
                    }
                }
            }
        } catch (Throwable $e) {
        }
    }
    return null;
}

function resolve_username_from_sources(array $record, array $session): ?string {
    $n = isset($record['user_name']) ? trim((string)$record['user_name']) : '';
    if ($n !== '') return $n;
    foreach (['username','user_name','name','display_name','login','handle'] as $k) {
        if (!empty($session[$k]) && is_scalar($session[$k])) {
            $s = trim((string)$session[$k]);
            if ($s !== '') return $s;
        }
    }
    foreach ([['user','username'],['user','name'],['auth','user','username'],['profile','username']] as $path) {
        $v = $session;
        foreach ($path as $p) { if (is_array($v) && array_key_exists($p, $v)) { $v = $v[$p]; } else { $v = null; break; } }
        if (is_scalar($v)) { $s = trim((string)$v); if ($s !== '') return $s; }
    }
    $email = (string)($record['user_email'] ?? ($session['user_email'] ?? ($session['email'] ?? '')));
    $lp = __email_local_part($email);
    if ($lp) return $lp;
    $uid = (int)($record['user_id'] ?? ($session['user_id'] ?? 0));
    if ($uid > 0) return __db_username_by_id($uid);
    return null;
}

// ---------- Core mail sender (can be reused if included) ----------
function send_order_mail(string $orderId): array {
    global $ADMIN_EMAIL,$FROM_EMAIL,$FROM_NAME,$SMTP_HOST,$SMTP_USER,$SMTP_PASS,$SMTP_PORT,$SMTP_SECURE;

    // Load order JSON
    $ordersDir = __DIR__ . '/storage/orders';
    $orderFile = $ordersDir . '/' . basename($orderId) . '.json';
    if (!is_file($orderFile)) {
        return ['ok' => false, 'error' => 'order_not_found'];
    }

    $json = @file_get_contents($orderFile);
    if ($json === false) return ['ok' => false, 'error' => 'read_failed'];

    $record = json_decode($json, true);
    if (!is_array($record)) return ['ok' => false, 'error' => 'json_invalid'];

    // Extract
    $items     = $record['items']      ?? [];
    $amounts   = $record['amounts']    ?? [];
    $totalTHB  = (float)($amounts['total'] ?? 0);
    $delivery  = (string)($record['delivery'] ?? '');
    $address   = (string)($record['address'] ?? '');
    $userId    = (int)   ($record['user_id'] ?? 0);
    $userEmail = (string)($record['user_email'] ?? '');
	$userName  = (string)($record['username'] ?? '');
    $slipWeb   = (string)($record['slip'] ?? '');
    $createdAt = (string)($record['created_at'] ?? '');

    // Build items table (HTML)
    $rows = '';
    foreach ($items as $it) {
        $name  = htmlspecialchars((string)($it['name'] ?? 'Item'));
        $qty   = (int)($it['qty'] ?? 1);
        $price = (float)($it['price'] ?? 0);
        $line  = $qty * $price;
        $rows .= '<tr>'
               . '<td style="padding:6px;border:1px solid #ddd;">'.$name.'</td>'
               . '<td style="padding:6px;border:1px solid #ddd;text-align:right;">'.$qty.'</td>'
               . '<td style="padding:6px;border:1px solid #ddd;text-align:right;">'.number_format($price,2).'</td>'
               . '<td style="padding:6px;border:1px solid #ddd;text-align:right;">'.number_format($line,2).'</td>'
               . '</tr>';
    }

    $addrHtml = nl2br(htmlspecialchars($address));
    $slipUrl  = htmlspecialchars($slipWeb);
    // Subject (single assignment)
$subject = 'New PromptPay order #'.$orderId.' awaiting review' . ($userName ? " ({$userName})" : '');

// HTML body (note the dots between segments)
$bodyHtml =
  '<div style="font-family:Inter,Arial,sans-serif;font-size:14px;color:#111;">'
. '  <h2 style="margin:0 0 8px 0;">New order received</h2>'
. '  <p style="margin:0 0 10px 0;">Order <strong>#'.htmlspecialchars($orderId).'</strong> is awaiting review.</p>'
. '  <table cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #ddd;width:100%;max-width:640px">'
. '    <thead>'
. '      <tr>'
. '        <th style="text-align:left;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Item</th>'
. '        <th style="text-align:right;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Qty</th>'
. '        <th style="text-align:right;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Price (THB)</th>'
. '        <th style="text-align:right;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Line (THB)</th>'
. '      </tr>'
. '    </thead>'
. '    <tbody>'.$rows.'</tbody>'
. '    <tfoot>'
. '      <tr>'
. '        <td colspan="3" style="text-align:right;padding:8px;border:1px solid #ddd;"><strong>Total</strong></td>'
. '        <td style="text-align:right;padding:8px;border:1px solid #ddd;"><strong>'.number_format($totalTHB,2).'</strong></td>'
. '      </tr>'
. '    </tfoot>'
. '  </table>'
. '  <p style="margin:12px 0 6px 0;"><strong>Delivery:</strong> '.htmlspecialchars($delivery).'</p>'
. (($delivery === 'ship' && $address) ? '  <p style="margin:0 0 6px 0;"><strong>Address:</strong><br>'.$addrHtml.'</p>' : '')
. '  <p style="margin:0 0 6px 0;"><strong>Username:</strong> '
. htmlspecialchars($userName)
. '</p>'
. ($userEmail ? '  <p style="margin:0 0 6px 0;"><strong>User Email:</strong> '.htmlspecialchars($userEmail).'</p>' : '')
. '  <p style="margin:0 0 6px 0;"><strong>Slip:</strong> <a href="'.$slipUrl.'" target="_blank" rel="noopener">View slip</a></p>'
. '  <p style="margin:0;color:#555;">Created at: '.htmlspecialchars($createdAt).'</p>'
. '</div>';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = $SMTP_USER;
        $mail->Password   = $SMTP_PASS;
        $mail->Port       = $SMTP_PORT;
        if ($SMTP_SECURE) $mail->SMTPSecure = $SMTP_SECURE;

        $mail->setFrom($FROM_EMAIL, $FROM_NAME);
        $mail->addAddress($ADMIN_EMAIL);
        if ($userEmail !== '') $mail->addReplyTo($userEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = strip_tags(
            "New order #{$orderId}\n".
            "Total: ".number_format($totalTHB,2)." THB\n".
            "Delivery: {$delivery}\n".
            ($delivery==='ship' && $address ? "Address:\n{$address}\n" : "").
            "Slip (web): {$slipWeb}\n".
            ($userName ? "User: {$userName}\n" : "").
            "Created: {$createdAt}\n"
        );

        // Attach slip (if present)
        if ($slipWeb !== '') {
            $slipAbs = __DIR__ . '/' . ltrim($slipWeb, '/');
            if (is_file($slipAbs)) {
                $mail->addAttachment($slipAbs, basename($slipAbs));
            }
        }

        $mail->send();
        return ['ok' => true];
    } catch (\Throwable $e) {
        // error_log('send_order mail failed: '.$e->getMessage());
        return ['ok' => false, 'error' => 'mail_failed', 'detail' => $e->getMessage()];
    }
}

// ---------- If called directly (HTTP): accept POST/GET order param ----------
$__direct = (isset($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']));
if ($__direct) {
   ini_set('display_errors','0');                    // keep JSON clean
    header('Content-Type: application/json; charset=utf-8');

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: [];

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    // Accept form, query, JSON, or fallback to session set by promptpay.php
    $orderParam = (string)(
        $_POST['order']      ??
        $_POST['order_id']   ??
        $_GET['order']       ??
        $_GET['order_id']    ??
        $payload['order']    ??
        $payload['order_id'] ??
        ($_SESSION['last_submitted_order'] ?? '')
    );
    session_write_close();

    if ($orderParam === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'missing_order']);
        exit;
    }

    $res = send_order_mail($orderParam);
    echo json_encode($res);
    exit;
}