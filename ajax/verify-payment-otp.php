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

$entered_otp = trim($_POST['otp'] ?? '');

if (!isset($_SESSION['payment_otp'])) {
    echo json_encode(['success' => false, 'message' => 'OTP session expired. Please request a new one.']);
    exit;
}

if ($entered_otp !== (string)$_SESSION['payment_otp']) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP! Please check and try again.']);
    exit;
}

// Clear OTP
unset($_SESSION['payment_otp']);
unset($_SESSION['payment_otp_time']);

echo json_encode(['success' => true, 'message' => 'Bank account verified securely!']);
