<?php
include_once 'database.php';

function sendResetEmail($email, $token) {
    global $lang;

    $resetLink = "https://futurexthailand.com/forgot_password.php?token=$token";

    // 1) Load config
    $cfgPath = __DIR__ . '/secure-config/futurex_mail.php';
    $config = file_exists($cfgPath) ? require $cfgPath : [
        'RESEND_API_KEY'    => getenv('RESEND_API_KEY'),
        'FROM_EMAIL_RESET'  => getenv('FROM_EMAIL_RESET'),
        'FROM_NAME_RESET'   => getenv('FROM_NAME_RESET'),
        'ADMIN_EMAIL'       => getenv('ADMIN_EMAIL'),
    ];

    // 2) Build email content
    if ($lang === 'th') {
        $subject = 'คำขอเปลี่ยนรหัสผ่าน';
        $html    = "
            <p>คุณขอการเปลี่ยนรหัสผ่านสำหรับบัญชี Future X ของคุณ</p>
            <p><a href='$resetLink' style='padding:10px 20px; background:#2563EB; color:#fff; text-decoration:none; border-radius:5px;'>เปลี่ยนรหัสผ่าน</a></p>
            <p>หากคุณไม่ได้ขอ คุณสามารถละเว้นอีเมลนี้ได้</p>
            <p>ลิงค์นี้จะหมดอายุใน <b>30 นาที</b></p>
        ";
    } else {
        $subject = 'Password Reset Request';
        $html    = "
            <p>You requested a password reset for your Future X account.</p>
            <p><a href='$resetLink' style='padding:10px 20px; background:#2563EB; color:#fff; text-decoration:none; border-radius:5px;'>Reset Password</a></p>
            <p>If you didn't request this, you can ignore this email.</p>
            <p>This link will expire in <b>30 minutes</b>.</p>
        ";
    }

    // 3) Send via Resend API
    $payload = json_encode([
        'from'    => $config['FROM_NAME_RESET'] . ' <' . $config['FROM_EMAIL_RESET'] . '>',
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
        error_log('Resend Error (sendResetEmail): HTTP ' . $httpCode . ' — ' . $response);
        return false;
    }
}