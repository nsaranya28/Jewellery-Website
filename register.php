<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
  $name     = trim($_POST['name'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  if (!$name || !$email || !$password) {
    flashMessage('error', 'Please fill all required fields.');
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flashMessage('error', 'Invalid email address.');
  } elseif (strlen($password) < 6) {
    flashMessage('error', 'Password must be at least 6 characters.');
  } elseif ($password !== $confirm) {
    flashMessage('error', 'Passwords do not match.');
  } else {
    $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $chk->execute([$email]);
    if ($chk->fetch()) {
      flashMessage('error', 'Email already registered. Please login.');
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?,?,?,?)")->execute([$name, $email, $phone, $hash]);
      $userId = $pdo->lastInsertId();
      $_SESSION['user_id'] = $userId;
      $_SESSION['user']    = ['id'=>$userId,'name'=>$name,'email'=>$email];
      flashMessage('success', "Welcome to Jewels.com, $name! 💎");
      header('Location: ' . ($_GET['redirect'] ?? 'index.php')); exit;
    }
  }
}

$pageTitle = 'Register — ' . SITE_NAME;
include 'includes/header.php';
?>

<section style="min-height:70vh;display:flex;align-items:center;background:linear-gradient(135deg,var(--ivory),var(--ivory-dark));">
  <div class="container">
    <div class="form-card" style="max-width:520px;">
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:42px;margin-bottom:8px;">💎</div>
        <h2>Create Account</h2>
        <p>Join Jewels.com and explore the finest jewellery</p>
      </div>

      <form method="POST">
        <div class="form-row">
          <div class="form-group"><label>Full Name *</label><input type="text" name="name" placeholder="Your full name" required value="<?= safeHtml($_POST['name'] ?? '') ?>"/></div>
          <div class="form-group"><label>Phone</label><input type="tel" name="phone" placeholder="10-digit mobile" value="<?= safeHtml($_POST['phone'] ?? '') ?>"/></div>
        </div>
        <div class="form-group"><label>Email Address *</label><input type="email" name="email" placeholder="you@email.com" required value="<?= safeHtml($_POST['email'] ?? '') ?>"/></div>
        <div class="form-row">
          <div class="form-group"><label>Password *</label><input type="password" name="password" placeholder="Minimum 6 characters" required/></div>
          <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm" placeholder="Re-enter password" required/></div>
        </div>
        <button type="submit" name="register" class="btn btn-gold btn-full" style="margin-top:8px;"><i class="fas fa-user-plus"></i> Create Account</button>
      </form>

      <div class="form-divider">or</div>
      <div class="form-link">Already have an account? <a href="login.php">Login here</a></div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
