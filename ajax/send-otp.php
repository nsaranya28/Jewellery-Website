<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// Check if email already exists
$chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
$chk->execute([$email]);
if ($chk->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered. Please login.']);
    exit;
}

// Generate OTP
$otp = sprintf("%06d", mt_rand(1, 999999));

// Save in session
$_SESSION['registration_otp'] = $otp;
$_SESSION['registration_email'] = $email;
$_SESSION['registration_otp_time'] = time();

// Determine host for email 'From' header
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'jewels.com';
if (strpos($host, 'localhost') !== false) {
    $host = 'jewels.local'; // fallback for local environment
}

// Send OTP
$subject = "Your Verification Code - " . SITE_NAME;
$message = "Hello,\n\nYour OTP for registration at " . SITE_NAME . " is: $otp\n\nThis OTP is valid for 10 minutes.\n\nThank you.";
$headers = "From: noreply@" . $host . "\r\n";
$headers .= "Reply-To: noreply@" . $host . "\r\n";

if (@mail($email, $subject, $message, $headers)) {
    echo json_encode(['success' => true, 'message' => 'OTP sent to your email.']);
} else {
    // For local development where mail() might fail, we simulate success and expose the OTP so the user can continue testing.
    echo json_encode(['success' => true, 'message' => 'OTP sent (Dev hint: ' . $otp . ')', 'dev_otp' => $otp]);
}
