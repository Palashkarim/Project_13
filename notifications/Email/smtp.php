<?php
// Simple SMTP wrapper using PHP's mail() or phpmailer (recommended).
// This file is a simple example. Replace with PHPMailer in prod for authentication.
declare(strict_types=1);

function send_email($to, $subject, $htmlBody, $from = null) {
    $from = $from ?? getenv('MAIL_FROM') ?: 'no-reply@example.com';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: {$from}\r\n";
    return mail($to, $subject, $htmlBody, $headers);
}
