<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function isAdminLoggedIn() { return isset($_SESSION['admin_id']); }
function requireAdmin() {
  if (!isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
  }
}
