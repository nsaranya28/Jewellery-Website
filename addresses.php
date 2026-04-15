<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

// Add address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
  if (isset($_POST['set_default'])) {
    $pdo->prepare("UPDATE addresses SET is_default=0 WHERE user_id=?")->execute([$_SESSION['user_id']]);
  }
  $isDefault = isset($_POST['set_default']) ? 1 : 0;
  $pdo->prepare("INSERT INTO addresses (user_id,label,full_name,phone,line1,line2,city,state,pincode,is_default) VALUES (?,?,?,?,?,?,?,?,?,?)")
      ->execute([$_SESSION['user_id'], $_POST['label'], $_POST['full_name'], $_POST['phone'], $_POST['line1'], $_POST['line2'] ?? '', $_POST['city'], $_POST['state'], $_POST['pincode'], $isDefault]);
  flashMessage('success', 'Address added!');
  header('Location: addresses.php'); exit;
}

// Delete
if (isset($_GET['delete'])) {
  $pdo->prepare("DELETE FROM addresses WHERE id=? AND user_id=?")->execute([(int)$_GET['delete'], $_SESSION['user_id']]);
  flashMessage('info', 'Address deleted.');
  header('Location: addresses.php'); exit;
}

// Set default
if (isset($_GET['default'])) {
  $pdo->prepare("UPDATE addresses SET is_default=0 WHERE user_id=?")->execute([$_SESSION['user_id']]);
  $pdo->prepare("UPDATE addresses SET is_default=1 WHERE id=? AND user_id=?")->execute([(int)$_GET['default'], $_SESSION['user_id']]);
  flashMessage('success', 'Default address updated.');
  header('Location: addresses.php'); exit;
}

$addrs = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, id DESC");
$addrs->execute([$_SESSION['user_id']]);
$addrs = $addrs->fetchAll();

$pageTitle = 'My Addresses — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header"><div class="container"><h1>📍 My Addresses</h1></div></div>

<section class="section">
  <div class="container">
    <div class="profile-layout">
      <div class="profile-sidebar">
        <div class="profile-avatar-section">
          <div class="profile-avatar"><?= mb_substr(currentUser()['name'],0,1) ?></div>
          <div class="profile-uname"><?= safeHtml(currentUser()['name']) ?></div>
        </div>
        <nav class="sidebar-nav">
          <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
          <a href="my-orders.php"><i class="fas fa-box"></i> My Orders</a>
          <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
          <a href="addresses.php" class="active"><i class="fas fa-location-dot"></i> Addresses</a>
          <a href="logout.php" style="color:var(--red);"><i class="fas fa-sign-out-alt" style="color:var(--red);"></i> Logout</a>
        </nav>
      </div>
      <div class="profile-content">
        <h2>Saved Addresses</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:28px;">
          <?php foreach ($addrs as $addr): ?>
          <div style="border:2px solid <?= $addr['is_default'] ? 'var(--gold)' : 'var(--gray-light)' ?>;border-radius:var(--radius-md);padding:18px;position:relative;background:<?= $addr['is_default'] ? 'var(--gold-pale)' : 'var(--white)' ?>;">
            <?php if ($addr['is_default']): ?>
            <span style="position:absolute;top:10px;right:10px;background:var(--gold);color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:50px;">DEFAULT</span>
            <?php endif; ?>
            <div style="font-weight:700;font-size:15px;color:var(--dark);margin-bottom:4px;"><?= safeHtml($addr['full_name']) ?></div>
            <div style="font-size:12px;color:var(--gray);line-height:1.6;"><?= safeHtml($addr['line1']) ?><?= $addr['line2'] ? ', '.safeHtml($addr['line2']) : '' ?><br/><?= safeHtml($addr['city']) ?>, <?= safeHtml($addr['state']) ?> - <?= safeHtml($addr['pincode']) ?></div>
            <div style="font-size:12px;color:var(--gray);margin-top:4px;"><i class="fas fa-phone"></i> <?= safeHtml($addr['phone']) ?></div>
            <div style="display:flex;gap:8px;margin-top:12px;">
              <?php if (!$addr['is_default']): ?>
              <a href="addresses.php?default=<?= $addr['id'] ?>" style="font-size:12px;color:var(--gold-dark);font-weight:600;"><i class="fas fa-star"></i> Set Default</a>
              <?php endif; ?>
              <a href="addresses.php?delete=<?= $addr['id'] ?>" onclick="return confirm('Delete this address?')" style="font-size:12px;color:var(--red);font-weight:600;margin-left:auto;"><i class="fas fa-trash"></i> Delete</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <h3 style="font-family:var(--font-serif);font-size:20px;color:var(--dark);margin-bottom:16px;">Add New Address</h3>
        <form method="POST">
          <div class="form-row">
            <div class="form-group">
              <label>Label</label>
              <select name="label"><option>Home</option><option>Work</option><option>Other</option></select>
            </div>
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required/></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Phone</label><input type="tel" name="phone" required/></div>
            <div class="form-group"><label>Pincode</label><input type="text" name="pincode" maxlength="6" required/></div>
          </div>
          <div class="form-group"><label>Address Line 1</label><input type="text" name="line1" required/></div>
          <div class="form-group"><label>Address Line 2 (optional)</label><input type="text" name="line2"/></div>
          <div class="form-row">
            <div class="form-group"><label>City</label><input type="text" name="city" required/></div>
            <div class="form-group"><label>State</label><input type="text" name="state" required/></div>
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--charcoal);margin-bottom:16px;cursor:pointer;">
            <input type="checkbox" name="set_default" style="accent-color:var(--gold);"/> Set as default address
          </label>
          <button type="submit" name="save_address" class="btn btn-gold"><i class="fas fa-plus"></i> Add Address</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
