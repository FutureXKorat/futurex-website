<?php
include 'database.php';

function sendOTPEmail($recipientEmail, $otp) {
    global $lang;

    // 1) Load config
    $config = require __DIR__ . '/secure-config/futurex_mail.php';

    // 2) Build email content
    if ($lang === 'th') {
        $subject = 'รหัส OTP Future X ของคุณ';
        $html    = "
            รหัส OTP ของคุณคือ: <b>$otp</b><br><br>
            กรุณาอย่าตอบกลับ ข้อความนี้เป็นข้อความอัตโนมัติ<br><br>
            รหัส OTP ของคุณจะหมดอายุภายใน <b>5 นาที</b>
        ";
    } else {
        $subject = 'Your Future X OTP Code';
        $html    = "
            Your OTP code is: <b>$otp</b><br><br>
            Please do not reply. This is an auto-message.<br><br>
            Your OTP code will expire in <b>5 mins</b>
        ";
    }

    // 3) Send via Resend API
    $payload = json_encode([
        'from'    => $config['FROM_NAME_OTP'] . ' <' . $config['FROM_EMAIL'] . '>',
        'to'      => [$recipientEmail],
        'subject' => $subject,
        'html'    => $html,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
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
        error_log('Resend Error (sendOTPEmail): HTTP ' . $httpCode . ' — ' . $response);
        return false;
    }
}