<?php
require_once 'includes/mail_config.php';
require_once 'includes/PHPMailerLite.php';

$to = 'nsaranya282@gmail.com';
$subject = "💎 OTP System Test - Jewels.com";
$message = "Hello!\n\nThis is a test email from your Jewels.com website.\n\n" .
           "Your OTP system is now ACTIVE and correctly configured.\n\n" .
           "If you are reading this, it means your Gmail App Password was accepted and emails are being sent successfully.\n\n" .
           "Best regards,\n" .
           "The Jewels.com Development Team";

echo "Testing SMTP connection to " . SMTP_HOST . " on port " . SMTP_PORT . "...\n";
echo "User: " . SMTP_USER . "\n";

$mailer = new PHPMailerLite(
    SMTP_HOST,
    SMTP_PORT,
    SMTP_USER,
    SMTP_PASS,
    SMTP_FROM,
    SMTP_NAME
);

if ($mailer->send($to, $subject, $message)) {
    echo "\n✅ SUCCESS: Email sent successfully! Please check your inbox at $to.\n";
} else {
    echo "\n❌ FAILURE: " . $mailer->getLastError() . "\n";
}
