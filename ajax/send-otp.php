<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$method = trim($_POST['method'] ?? 'email'); // 'email' or 'phone'
$email  = trim($_POST['email'] ?? '');
$phone  = trim($_POST['phone'] ?? '');

// ── Validate based on chosen method ──
if ($method === 'email') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    $contact = $email;

    // Check duplicate
    $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $chk->execute([$email]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered. Please login.']);
        exit;
    }
} elseif ($method === 'phone') {
    // Remove spaces, dashes, and validate 10-digit Indian mobile
    $phone = preg_replace('/[\s\-]/', '', $phone);
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
        exit;
    }
    $contact = $phone;

    // Check duplicate
    $chk = $pdo->prepare("SELECT id FROM users WHERE phone=?");
    $chk->execute([$phone]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Phone number already registered. Please login.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid verification method.']);
    exit;
}

// ── Rate limit: 60 seconds between OTP requests ──
if (!empty($_SESSION['registration_otp_time']) && (time() - $_SESSION['registration_otp_time']) < 60) {
    $wait = 60 - (time() - $_SESSION['registration_otp_time']);
    echo json_encode(['success' => false, 'message' => "Please wait {$wait} seconds before requesting a new OTP."]);
    exit;
}

// ── Generate OTP ──
$otp = sprintf("%06d", mt_rand(100000, 999999));

// ── Save in session ──
$_SESSION['registration_otp']        = $otp;
$_SESSION['registration_otp_method'] = $method;
$_SESSION['registration_otp_contact']= $contact;
$_SESSION['registration_otp_time']   = time();

// ── Send OTP ──
if ($method === 'email') {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'jewels.com';
    if (strpos($host, 'localhost') !== false) {
        $host = 'jewels.local';
    }
    $subject = "Your Verification Code — " . SITE_NAME;
    $message = "Hello,\n\nYour OTP for registration at " . SITE_NAME . " is: $otp\n\nThis code is valid for 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nThank you,\n" . SITE_NAME;
    $headers = "From: noreply@" . $host . "\r\n";
    $headers .= "Reply-To: noreply@" . $host . "\r\n";

    if (@mail($email, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => "OTP sent to $email", 'method' => 'email']);
    } else {
        // Dev fallback — mail() may not work on localhost
        echo json_encode(['success' => true, 'message' => "OTP sent to $email", 'method' => 'email', 'dev_otp' => $otp]);
    }
} else {
    // Phone — In production, integrate an SMS gateway (Twilio, MSG91, etc.)
    // For now, simulate success and expose OTP for dev/testing
    $masked = 'XXXXXX' . substr($phone, -4);
    echo json_encode(['success' => true, 'message' => "OTP sent to $masked", 'method' => 'phone', 'dev_otp' => $otp]);
}
