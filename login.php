<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email']];
    // Merge guest cart
    $pdo->prepare("UPDATE cart SET user_id=?, session_id=NULL WHERE session_id=? AND product_id NOT IN (SELECT p FROM (SELECT product_id AS p FROM cart WHERE user_id=?) AS tmp)")
        ->execute([$user['id'], cartKey(), $user['id']]);
    $pdo->prepare("DELETE FROM cart WHERE session_id=?")->execute([cartKey()]);
    flashMessage('success', 'Welcome back, ' . explode(' ',$user['name'])[0] . '! 💎');
    header('Location: ' . urldecode($_GET['redirect'] ?? 'index.php')); exit;
  } else {
    flashMessage('error', 'Invalid email or password.');
  }
}

$pageTitle = 'Login — ' . SITE_NAME;
include 'includes/header.php';
?>

<section style="min-height:70vh;display:flex;align-items:center;background:linear-gradient(135deg,var(--ivory),var(--ivory-dark));">
  <div class="container">
    <div class="form-card">
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:42px;margin-bottom:8px;">💍</div>
        <h2>Welcome Back</h2>
        <p>Login to your Jewels.com account</p>
      </div>

      <form method="POST">
        <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="you@email.com" required value="<?= safeHtml($_POST['email'] ?? '') ?>"/></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Your password" required/></div>
        <button type="submit" class="btn btn-gold btn-full" style="margin-top:8px;"><i class="fas fa-sign-in-alt"></i> Login</button>
      </form>

      <div style="background:var(--gold-pale);border:1px solid var(--gold);border-radius:var(--radius-sm);padding:10px 14px;margin:16px 0;font-size:12px;color:var(--gold-dark);">
        <strong>Demo Login:</strong> priya@gmail.com / user123
      </div>

      <div class="form-divider">or</div>
      <div class="form-link">New to Jewels.com? <a href="register.php">Create an account</a></div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
