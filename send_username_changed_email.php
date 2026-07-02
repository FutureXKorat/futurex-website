<?php
include_once 'database.php';

function sendUsernameChangedEmail($email, $name, $oldUsername, $newUsername) {
    global $lang;

    // 1) Load config
    $cfgPath = __DIR__ . '/secure-config/futurex_mail.php';
    $config = file_exists($cfgPath) ? require $cfgPath : [
        'RESEND_API_KEY'     => getenv('RESEND_API_KEY'),
        'FROM_EMAIL_ACCOUNT' => getenv('FROM_EMAIL_ACCOUNT'),
        'FROM_NAME_ACCOUNT'  => getenv('FROM_NAME_ACCOUNT'),
    ];
    $fromEmail = $config['FROM_EMAIL_ACCOUNT'] ?? getenv('FROM_EMAIL_ACCOUNT') ?: 'account@futurexthailand.com';
    $fromName  = $config['FROM_NAME_ACCOUNT']  ?? getenv('FROM_NAME_ACCOUNT')  ?: 'Future X Account';

    // 2) Build email content
    if ($lang === 'th') {
        $subject = 'ชื่อผู้ใช้ของคุณถูกเปลี่ยน';
        $html    = "
            <p>สวัสดีคุณ" . htmlspecialchars($name) . "</p>
            <p>ทีมงาน Future X ได้เปลี่ยนชื่อผู้ใช้สำหรับบัญชีของคุณ:</p>
            <p>ชื่อผู้ใช้เดิม: <b>" . htmlspecialchars($oldUsername) . "</b><br>
               ชื่อผู้ใช้ใหม่: <b>" . htmlspecialchars($newUsername) . "</b></p>
            <p>หากคุณไม่ได้ร้องขอการเปลี่ยนแปลงนี้ กรุณาติดต่อทีมงาน Future X ทันที</p>
        ";
    } else {
        $subject = 'Your Username Has Been Changed';
        $html    = "
            <p>Hi " . htmlspecialchars($name) . ",</p>
            <p>The Future X team has changed the username on your account:</p>
            <p>Old username: <b>" . htmlspecialchars($oldUsername) . "</b><br>
               New username: <b>" . htmlspecialchars($newUsername) . "</b></p>
            <p>If you did not request this change, please contact the Future X team immediately.</p>
        ";
    }

    // 3) Send via Resend API
    $payload = json_encode([
        'from'    => $fromName . ' <' . $fromEmail . '>',
        'to'      => [$email],
        'subject' => $subject,
        'html'    => $html,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['RESEND_API_KEY'],
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // 4) Check result
    if ($httpCode === 200 || $httpCode === 201) {
        return true;
    } else {
        error_log('Resend Error (sendUsernameChangedEmail): HTTP ' . $httpCode . ' — ' . $response);
        return false;
    }
}
