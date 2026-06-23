<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

include 'databae.php';

function sendResetEmail($email, $token) {
    // Password reset link (keep your current link)
    $resetLink = "https://futurexthailand.com/reset_password.php?token=$token";

    $mail = new PHPMailer(true);

    global $lang;

    try {
        // 1) Load secret config FIRST (outside web root)
        $config = require __DIR__ . '/secure-config/futurex_mail.php';
        // For XAMPP (localhost) practice, use this instead:
        // $config = require 'C:/xampp/secure-config/canasia_mail.php';

        // 2) SMTP configuration (from secret config)
        $mail->isSMTP();
        $mail->Host       = $config['SMTP_HOST'];
        $mail->SMTPAuth   = (bool)$config['SMTP_AUTH'];
        $mail->Username   = $config['SMTP_USER'];
        $mail->Password   = $config['SMTP_PASS'];
        $mail->SMTPSecure = $config['SMTP_SECURE']; // 'tls' or 'ssl'
        $mail->Port       = (int)$config['SMTP_PORT'];
        $mail->CharSet    = 'UTF-8';
        // $mail->SMTPDebug = 0; // keep 0 in production

        // 3) Sender & recipient
        $mail->setFrom($config['FROM_EMAIL'], $config['FROM_NAME_RESET']);
        $mail->addAddress($email);

        // 4) Email content
        $mail->isHTML(true);
        $mail->Subject = ($lang === 'en') ? 'Password Reset Request' : 'คำขอเปลี่ยนรหัสผ่าน';
        if ($lang === 'en') {
            $mail->Body = "
                <p>You requested a password reset for your Future X account.</p>
                <p><a href='$resetLink' style='padding:10px 20px; background:#2563EB; color:#fff; text-decoration:none; border-radius:5px;'>Reset Password</a></p>
                <p>If you didn’t request this, you can ignore this email.</p>
                <p>This link will expire in <b>30 minutes</b>.</p>
            ";
            $mail->AltBody = "Use this link to reset your password: $resetLink";
        }elseif ($lang === 'th') {
            $mail->Body = "
                <p>คุณขอการเปลี่ยนรหัสผ่านสำหรับบัญชี Future X ของคุณ</p>
                <p><a href='$resetLink' style='padding:10px 20px; background:#2563EB; color:#fff; text-decoration:none; border-radius:5px;'>เปลี่ยนรหัสผ่าน</a></p>
                <p>หากคุณไม่ได้ขอ คุณสามารถละเว้นอีเมลนี้ได้</p>
                <p>ลิงค์นี้จะหมดอายุใน <b>30 นาที</b></p>
            ";
            $mail->AltBody = "ใช้ลิงก์นี้เพื่อเปลี่ยนรหัสผ่านของคุณ: $resetLink";
        }

        // 5) Send
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mailer Error (sendResetEmail): ' . $mail->ErrorInfo);
        return false;
    }
}
