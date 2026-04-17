<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
  $name     = trim($_POST['name'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $otp      = trim($_POST['otp'] ?? '');

  if (!$name || !$email || !$password || !$otp) {
    flashMessage('error', 'Please fill all required fields, including OTP.');
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flashMessage('error', 'Invalid email address.');
  } elseif (strlen($password) < 6) {
    flashMessage('error', 'Password must be at least 6 characters.');
  } elseif ($password !== $confirm) {
    flashMessage('error', 'Passwords do not match.');
  } elseif (!isset($_SESSION['registration_otp']) || $_SESSION['registration_email'] !== $email || $_SESSION['registration_otp'] !== $otp) {
    flashMessage('error', 'Invalid or expired OTP.');
  } else {
    $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $chk->execute([$email]);
    if ($chk->fetch()) {
      flashMessage('error', 'Email already registered. Please login.');
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?,?,?,?)")->execute([$name, $email, $phone, $hash]);
      $userId = $pdo->lastInsertId();
      
      // Clear OTP session data
      unset($_SESSION['registration_otp']);
      unset($_SESSION['registration_email']);
      unset($_SESSION['registration_otp_time']);
      
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
        <div class="form-group">
          <label>Email Address *</label>
          <div style="display:flex; gap:10px;">
            <input type="email" id="reg-email" name="email" placeholder="you@email.com" required value="<?= safeHtml($_POST['email'] ?? '') ?>" style="flex:1;"/>
            <button type="button" id="btn-send-otp" class="btn btn-outline" style="white-space:nowrap; padding:0 20px;">Get OTP</button>
          </div>
        </div>
        <div class="form-group">
          <label>Enter OTP *</label>
          <input type="text" name="otp" placeholder="6-digit OTP" required/>
        </div>
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

<script>
document.getElementById('btn-send-otp').addEventListener('click', function() {
    const email = document.getElementById('reg-email').value;
    if (!email) {
        alert('Please enter your email address first.');
        return;
    }
    
    // Basic email validation regex
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please enter a valid email address.');
        return;
    }

    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Sending...';

    const formData = new FormData();
    formData.append('email', email);

    fetch('ajax/send-otp.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Start countdown
            let timeLeft = 60;
            const timer = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = 'Get OTP';
                } else {
                    btn.textContent = `Wait ${timeLeft}s`;
                    timeLeft--;
                }
            }, 1000);
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.textContent = 'Get OTP';
        }
    })
    .catch(err => {
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Get OTP';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
