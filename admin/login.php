<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';

if (isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/admin/'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $stmt     = $pdo->prepare("SELECT * FROM admins WHERE email=?");
  $stmt->execute([$email]);
  $admin = $stmt->fetch();
  if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    header('Location: ' . SITE_URL . '/admin/'); exit;
  } else {
    $error = 'Invalid credentials.';
  }
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Admin Login — Jewels.com</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#1a1207,#3d2b00);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.card{background:#fff;border-radius:20px;padding:44px 40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.4);}
.logo{text-align:center;margin-bottom:28px;}
.logo-icon{font-size:44px;margin-bottom:8px;}
.logo h2{font-size:24px;font-weight:700;color:#1a1207;}
.logo p{font-size:13px;color:#9a7a50;margin-top:4px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:12px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:7px;}
.form-group input{width:100%;padding:12px 16px;border:1.5px solid #ededed;border-radius:10px;font-size:14px;outline:none;transition:border-color 0.2s,box-shadow 0.2s;}
.form-group input:focus{border-color:#c9a227;box-shadow:0 0 0 3px rgba(201,162,39,0.1);}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#8b6914,#c9a227);color:#fff;border:none;border-radius:50px;font-size:14px;font-weight:700;cursor:pointer;margin-top:6px;transition:all 0.2s;}
.btn:hover{box-shadow:0 6px 20px rgba(201,162,39,0.4);}
.error{background:#f8d7da;color:#721c24;padding:12px;border-radius:10px;font-size:13px;margin-bottom:16px;}
.flash{padding:12px;border-radius:10px;font-size:13px;margin-bottom:16px;}
.flash-success{background:#d4edda;color:#155724;}
.flash-error{background:#f8d7da;color:#721c24;}
.flash-info{background:rgba(201,162,39,0.1);color:#8b6914;}
.hint{background:rgba(201,162,39,0.1);border:1px solid #f5d16b;border-radius:10px;padding:10px 14px;font-size:12px;color:#8b6914;margin-top:14px;}
</style>
</head><body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">🔑</div>
    <h2>Admin Login</h2>
    <p>Jewels.com Administration Panel</p>
  </div>
  <?php showFlash(); ?>
  <?php if (!empty($error)): ?><div class="error">❌ <?= safeHtml($error) ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="admin@jewels.com" value="<?= safeHtml($_POST['email'] ?? '') ?>"/></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="Your admin password"/></div>
    <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Login to Admin</button>
  </form>
  <div class="hint"><strong>Demo:</strong> admin@jewels.com / admin123</div>
</div>
</body></html>
