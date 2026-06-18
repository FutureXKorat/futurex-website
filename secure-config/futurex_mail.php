<?php
declare(strict_types=1);

/**
 * Private mail config for PHPMailer (keep this file OUTSIDE the web root).
 * Do NOT upload this inside htdocs/public_html.
 * Replace the placeholder values below with your own.
 */

return [
    // Gmail SMTP
    'SMTP_HOST'   => 'smtp.gmail.com',
    'SMTP_PORT'   => 587,            // use 465 if your host only allows SSL
    'SMTP_SECURE' => 'tls',          // use 'ssl' if you switch to port 465
    'SMTP_AUTH'   => true,

    // Your Gmail (the same account you created the App Password for)
    'SMTP_USER'   => 'futurexkorat@gmail.com',   // ← change this
    'SMTP_PASS'   => 'iihaxfjidnqnpazw',      // ← change this (App Password, not your real login)

    // From headers (best practice: match the Gmail you authenticate with)
    'FROM_EMAIL'  => 'futurexkorat@gmail.com',   // ← usually same as SMTP_USER
    'FROM_NAME_OTP'   => 'Future X OTP',                         // ← shown name in recipients’ inboxes
    'FROM_NAME_RESET'   => 'Future X Reset Email',
    
    'FROM_NAME_ORDER' => 'Future X Order Form',
  	'ADMIN_EMAIL'     => 'futurexkorat@gmail.com', 
];
