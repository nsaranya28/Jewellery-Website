<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Unset all admin-related session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);

// Optional: destroy the whole session if needed, 
// but usually we just want to logout the admin part.
// session_destroy(); 

flashMessage('success', 'Logged out successfully.');
header('Location: login.php');
exit;
