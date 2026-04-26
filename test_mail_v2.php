<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/mailer.php';

$test_email = 'saranya02022005@gmail.com'; 
$subject = "Test Email v2 from Jewels.com";
$message = "This is a fresh test email to bypass any caching issues.";

echo "<h1>SMTP Test v2</h1>";

if (function_exists('send_mail_smtp')) {
    $result = send_mail_smtp($test_email, $subject, $message);
    if ($result === true) {
        echo "<b style='color:green;'>SUCCESS: Email sent successfully!</b>";
    } else {
        echo "<b style='color:red;'>FAILED: Email sending failed.</b><br>";
        echo "<pre>" . htmlspecialchars($result) . "</pre>";
    }
} else {
    echo "<b style='color:red;'>ERROR: Function send_mail_smtp still not defined!</b>";
}
?>
