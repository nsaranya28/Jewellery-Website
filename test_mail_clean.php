<?php
require_once 'includes/mail_config.php';
require_once 'includes/PHPMailerLite.php';

$to = 'nsaranya282@gmail.com';
$subject = "💎 OTP System Test - Jewels.com";
$message = "Test email.";

// Try with spaces removed
$clean_pass = str_replace(' ', '', SMTP_PASS);

echo "Testing with cleaned password (no spaces)...\n";

$mailer = new PHPMailerLite(
    SMTP_HOST,
    SMTP_PORT,
    SMTP_USER,
    $clean_pass,
    SMTP_FROM,
    SMTP_NAME
);

if ($mailer->send($to, $subject, $message)) {
    echo "\n✅ SUCCESS: Email sent successfully!\n";
} else {
    echo "\n❌ FAILURE: " . $mailer->getLastError() . "\n";
}
