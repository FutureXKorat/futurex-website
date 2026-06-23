<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

include 'database.php';

function sendOTPEmail($recipientEmail, $otp) {
    $mail = new PHPMailer(true);
    global $lang;

    try {
        // 1) Load secret config FIRST (outside web root)
        $config = require '/secure-config/futurex_mail.php';
        // For XAMPP (localhost) practice, use this instead:
        // $config = require 'C:/xampp/secure-config/futurex_mail.php';

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

        // 3) Headers & recipients
        $mail->setFrom($config['FROM_EMAIL'], $config['FROM_NAME_OTP']);
        $mail->addAddress($recipientEmail);

        // 4) Content
        $mail->isHTML(true);
        $mail->Subject = ($lang === 'en') ? 'Your Future X OTP Code' : 'รหัส OTP Future X ของคุณ';
        if ($lang === 'en') {
        $mail->Body = "
            Your OTP code is: <b>$otp</b><br><br>
            Please do not reply. This is an auto-message.<br><br>
	    	Your OTP code will expire in <b>5 mins</b>
        ";
        $mail->AltBody = "Your OTP code is: $otp";
        }elseif ($lang === 'th') {
            $mail->Body = "
            รหัส OTP ของคุณคือ: <b>$otp</b><br><br>
            กรุณาอย่าตอบกลับ ข้อความนี้เป็นข้อความอัตโนมัติ<br><br>
	        รหัส  OTP ของคุณจะหมดอายุภายใน <b>5 นาที</b>
        ";
        $mail->AltBody = "รหัส OTP ของคุณคือ: $otp";
        }

        // 5) Send
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the detailed error; avoid exposing internals to users
        error_log('Mailer Error (sendOTPEmail): ' . $mail->ErrorInfo);
        return false;
    }
}
