<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$otp = trim($_POST['otp'] ?? '');

// ── Validate OTP exists in session ──
if (empty($_SESSION['registration_otp'])) {
    echo json_encode(['success' => false, 'message' => 'No OTP was requested. Please go back and request one.']);
    exit;
}

// ── Check expiry (10 minutes) ──
if ((time() - $_SESSION['registration_otp_time']) > 600) {
    unset($_SESSION['registration_otp'], $_SESSION['registration_otp_time'], $_SESSION['registration_otp_method'], $_SESSION['registration_otp_contact']);
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
    exit;
}

// ── Verify OTP ──
if ($otp !== $_SESSION['registration_otp']) {
    echo json_encode(['success' => false, 'message' => 'Incorrect OTP. Please try again.']);
    exit;
}

// ── OTP is correct — mark session as verified ──
$_SESSION['registration_verified'] = true;
$_SESSION['registration_verified_method']  = $_SESSION['registration_otp_method'];
$_SESSION['registration_verified_contact'] = $_SESSION['registration_otp_contact'];

// Clean up OTP from session
unset($_SESSION['registration_otp'], $_SESSION['registration_otp_time']);

echo json_encode(['success' => true, 'message' => 'Verification successful! Creating your account…']);
