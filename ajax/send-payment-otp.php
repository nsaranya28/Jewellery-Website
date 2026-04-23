<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to checkout.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Generate OTP
$otp = sprintf("%06d", mt_rand(1, 999999));

// Save in session
$_SESSION['payment_otp'] = $otp;
$_SESSION['payment_otp_time'] = time();

// Send OTP response (simulated SMS)
echo json_encode(['success' => true, 'message' => 'OTP sent securely to your registered bank mobile number.', 'dev_otp' => $otp]);
