<?php
// file: send_order.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Config ----------
$cfgPath = __DIR__ . '/secure-config/futurex_mail.php';
$mailCfg = is_file($cfgPath) ? require $cfgPath : [];

$ADMIN_EMAIL    = (string)($mailCfg['ADMIN_EMAIL']      ?? getenv('ADMIN_EMAIL')      ?: 'futurexkorat@gmail.com');
$FROM_EMAIL     = (string)($mailCfg['FROM_EMAIL_ORDER'] ?? getenv('FROM_EMAIL_ORDER') ?: 'order@futurexthailand.com');
$FROM_NAME      = (string)($mailCfg['FROM_NAME_ORDER']  ?? getenv('FROM_NAME_ORDER')  ?: 'Future X Order');
$RESEND_API_KEY = (string)($mailCfg['RESEND_API_KEY']   ?? getenv('RESEND_API_KEY')   ?: '');

function __email_local_part(?string $email): ?string {
    $e = trim((string)$email);
    if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL)) return null;
    $lp = strstr($e, '@', true);
    return ($lp !== false && $lp !== '') ? $lp : null;
}

function __db_pdo(): ?PDO {
    $cfgPath = __DIR__ . '/secure-config/db.php';
    $dbcfg = is_file($cfgPath) ? require $cfgPath : [];
    $dsn  = getenv('DB_DSN')  ?: ($dbcfg['dsn']  ?? null);
    $user = getenv('DB_USER') ?: ($dbcfg['user'] ?? null);
    $pass = getenv('DB_PASS') ?? ($dbcfg['pass'] ?? '');
    if (!$dsn || !$user) return null;
    try {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
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
        } catch (Throwable $e) {}
    }
    return null;
}

// ---------- Core mail sender ----------
function send_order_mail(string $orderId): array {
    global $ADMIN_EMAIL, $FROM_EMAIL, $FROM_NAME, $RESEND_API_KEY, $conn;

    // Load order from MySQL
    if (!($conn instanceof mysqli)) return ['ok' => false, 'error' => 'no_db'];
    $stmt = $conn->prepare("SELECT data FROM `orders` WHERE order_id = ? LIMIT 1");
    if (!$stmt) return ['ok' => false, 'error' => 'prepare_failed'];
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return ['ok' => false, 'error' => 'order_not_found'];

    $record = json_decode((string)$row['data'], true);
    if (!is_array($record)) return ['ok' => false, 'error' => 'json_invalid'];

    // Extract fields
    $items     = $record['items']      ?? [];
    $amounts   = $record['amounts']    ?? [];
    $totalTHB  = (float)($amounts['total'] ?? 0);
    $delivery  = (string)($record['delivery']   ?? '');
    $address   = (string)($record['address']    ?? '');
    $userEmail = (string)($record['user_email'] ?? '');
    $userName  = (string)($record['username']   ?? '');
    $slipWeb   = (string)($record['slip']       ?? '');
    $createdAt = (string)($record['created_at'] ?? '');

    // Build items table rows
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
    $slipUrl  = htmlspecialchars('https://www.futurexthailand.com/' . $slipWeb);
    $subject  = 'New PromptPay order #'.$orderId.' awaiting review'.($userName ? " ({$userName})" : '');

    $bodyHtml =
      '<div style="font-family:Inter,Arial,sans-serif;font-size:14px;color:#111;">'
    . '<h2 style="margin:0 0 8px 0;">New order received</h2>'
    . '<p style="margin:0 0 10px 0;">Order <strong>#'.htmlspecialchars($orderId).'</strong> is awaiting review.</p>'
    . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #ddd;width:100%;max-width:640px">'
    . '<thead><tr>'
    . '<th style="text-align:left;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Item</th>'
    . '<th style="text-align:right;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Qty</th>'
    . '<th style="text-align:right;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Price (THB)</th>'
    . '<th style="text-align:right;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Line (THB)</th>'
    . '</tr></thead>'
    . '<tbody>'.$rows.'</tbody>'
    . '<tfoot><tr>'
    . '<td colspan="3" style="text-align:right;padding:8px;border:1px solid #ddd;"><strong>Total</strong></td>'
    . '<td style="text-align:right;padding:8px;border:1px solid #ddd;"><strong>'.number_format($totalTHB,2).'</strong></td>'
    . '</tr></tfoot>'
    . '</table>'
    . '<p style="margin:12px 0 6px 0;"><strong>Delivery:</strong> '.htmlspecialchars($delivery).'</p>'
    . (($delivery === 'ship' && $address) ? '<p style="margin:0 0 6px 0;"><strong>Address:</strong><br>'.$addrHtml.'</p>' : '')
    . '<p style="margin:0 0 6px 0;"><strong>Username:</strong> '.htmlspecialchars($userName).'</p>'
    . ($userEmail ? '<p style="margin:0 0 6px 0;"><strong>User Email:</strong> '.htmlspecialchars($userEmail).'</p>' : '')
    . '<p style="margin:0 0 6px 0;"><strong>Slip:</strong> <a href="'.$slipUrl.'" target="_blank" rel="noopener">View slip</a></p>'
    . '<p style="margin:0;color:#555;">Created at: '.htmlspecialchars($createdAt).'</p>'
    . '</div>';

    // Build payload
    $payload = [
        'from'    => $FROM_NAME . ' <' . $FROM_EMAIL . '>',
        'to'      => [$ADMIN_EMAIL],
        'subject' => $subject,
        'html'    => $bodyHtml,
    ];
    if ($userEmail !== '') {
        $payload['reply_to'] = $userEmail;
    }

    // Send via Resend API
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $RESEND_API_KEY,
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200 || $httpCode === 201) {
        return ['ok' => true];
    } else {
        error_log('Resend Error (send_order_mail): HTTP ' . $httpCode . ' — ' . $response);
        return ['ok' => false, 'error' => 'mail_failed', 'detail' => $response];
    }
}

// ---------- If called directly via HTTP ----------
$__direct = (isset($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']));
if ($__direct) {
    ini_set('display_errors', '0');
    header('Content-Type: application/json; charset=utf-8');

    $raw     = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: [];

    if (session_status() === PHP_SESSION_NONE) { session_start(); }

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