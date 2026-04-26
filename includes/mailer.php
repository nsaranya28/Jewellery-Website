<?php
require_once 'mail_config.php';
require_once 'PHPMailerLite.php';

/**
 * Sends a real email using Gmail SMTP.
 */
function send_mail_smtp($to, $subject, $message) {
    if (empty(SMTP_PASS)) {
        return "SMTP_PASS is not configured in mail_config.php";
    }

    $mailer = new PHPMailerLite(
        SMTP_HOST,
        SMTP_PORT,
        SMTP_USER,
        SMTP_PASS,
        SMTP_FROM,
        SMTP_NAME
    );

    if ($mailer->send($to, $subject, $message)) {
        return true;
    }
    return $mailer->getLastError();
}
