<?php
require_once 'includes/mailer.php';

// Test email address
$test_email = 'saranya02022005@gmail.com'; 
$subject = "Test Email from Jewels.com";
$message = "This is a test email to verify SMTP configuration.";

echo "<h1>SMTP Test</h1>";
echo "Attempting to send email to $test_email...<br>";

$result = send_mail_smtp($test_email, $subject, $message);

if ($result === true) {
    echo "<b style='color:green;'>SUCCESS: Email sent successfully!</b>";
} else {
    echo "<b style='color:red;'>FAILED: Email sending failed.</b><br>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
}
?>
