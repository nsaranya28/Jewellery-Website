<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = trim($_POST['name'] ?? $user['name']);
  $phone = trim($_POST['phone'] ?? $user['phone']);
  $pdo->prepare("UPDATE users SET name=?, phone=? WHERE id=?")->execute([$name, $phone, $_SESSION['user_id']]);
  if (!empty($_POST['new_password'])) {
    if (!password_verify($_POST['current_password'], $user['password'])) {
      flashMessage('error', 'Current password is incorrect.');
    } elseif (strlen($_POST['new_password']) < 6) {
      flashMessage('error', 'New password must be at least 6 characters.');
    } else {
      $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
      flashMessage('success', 'Password updated successfully.');
    }
  } else {
    flashMessage('success', 'Profile updated successfully!');
  }
  $_SESSION['user']['name'] = $name;
  header('Location: profile.php'); exit;
}

$orderCount = (int)$pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?")->execute([$_SESSION['user_id']]) ? $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id={$_SESSION['user_id']}")->fetchColumn() : 0;
$cntO = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $cntO->execute([$_SESSION['user_id']]); $orderCount = (int)$cntO->fetchColumn();
$cntW = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?"); $cntW->execute([$_SESSION['user_id']]); $wishCount = (int)$cntW->fetchColumn();

$pageTitle = 'My Profile — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>👤 My Account</h1>
    <div class="breadcrumb"><a href="index.php">Home</a> <i class="fas fa-chevron-right"></i> <span>My Profile</span></div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="profile-layout">

      <!-- sidebar -->
      <div class="profile-sidebar">
        <div class="profile-avatar-section">
          <div class="profile-avatar"><?= mb_substr($user['name'],0,1) ?></div>
          <div class="profile-uname"><?= safeHtml($user['name']) ?></div>
          <div class="profile-email"><?= safeHtml($user['email']) ?></div>
        </div>
        <nav class="sidebar-nav">
          <a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a>
          <a href="my-orders.php"><i class="fas fa-box"></i> My Orders <span style="background:var(--gold-pale);color:var(--gold-dark);border-radius:50px;padding:1px 8px;font-size:11px;margin-left:auto;"><?= $orderCount ?></span></a>
          <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist <span style="background:var(--gold-pale);color:var(--gold-dark);border-radius:50px;padding:1px 8px;font-size:11px;margin-left:auto;"><?= $wishCount ?></span></a>
          <a href="addresses.php"><i class="fas fa-location-dot"></i> Addresses</a>
          <a href="logout.php" style="color:var(--red);"><i class="fas fa-sign-out-alt" style="color:var(--red);"></i> Logout</a>
        </nav>
      </div>

      <!-- content -->
      <div class="profile-content">
        <h2>Edit Profile</h2>
        <form method="POST">
          <div class="form-row">
            <div class="form-group"><label>Full Name</label><input type="text" name="name" value="<?= safeHtml($user['name']) ?>" required/></div>
            <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="<?= safeHtml($user['phone'] ?? '') ?>"/></div>
          </div>
          <div class="form-group"><label>Email (cannot change)</label><input type="email" value="<?= safeHtml($user['email']) ?>" readonly style="background:var(--gray-light);cursor:not-allowed;"/></div>
          <div style="padding:20px;background:var(--ivory-dark);border-radius:var(--radius-md);margin:20px 0;">
            <h3 style="font-size:16px;font-weight:600;color:var(--dark);margin-bottom:14px;">Change Password</h3>
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password"/></div>
            <div class="form-row">
              <div class="form-group"><label>New Password</label><input type="password" name="new_password" placeholder="Min 6 characters"/></div>
              <div class="form-group"><label>Confirm New</label><input type="password" name="confirm_password" placeholder="Re-enter"/></div>
            </div>
          </div>
          <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
