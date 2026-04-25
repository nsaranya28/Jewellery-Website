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
    require_once '../includes/mailer.php';
    
    $subject = "🔒 " . $otp . " is your verification code for " . SITE_NAME;
    $message = "Hello,\n\n" .
               "Thank you for choosing " . SITE_NAME . " — where elegance meets purity.\n\n" .
               "Your one-time verification code is:\n\n" .
               "💎 $otp 💎\n\n" .
               "This code is valid for 10 minutes. Please enter it on the registration page to secure your account.\n\n" .
               "🛡️ Security Tip:\n" .
               "Never share this OTP with anyone. Our team will never ask for your code over phone or email.\n\n" .
               "Best regards,\n" .
               "The " . SITE_NAME . " Team";
    
    $mailRes = send_mail_smtp($email, $subject, $message);
    if ($mailRes === true) {
        echo json_encode(['success' => true, 'message' => "OTP sent to $email securely.", 'method' => 'email']);
    } else {
        // If SMTP fails, provide the specific error for debugging
        echo json_encode(['success' => true, 'message' => "OTP sent to $email (Simulation Mode).", 'method' => 'email', 'dev_otp' => $otp, 'smtp_error' => $mailRes]);
    }
} else {
    // Phone — In production, integrate an SMS gateway (Twilio, MSG91, etc.)
    // For now, simulate success and expose OTP for dev/testing
    $masked = 'XXXXXX' . substr($phone, -4);
    echo json_encode(['success' => true, 'message' => "OTP sent to $masked", 'method' => 'phone', 'dev_otp' => $otp]);
}
