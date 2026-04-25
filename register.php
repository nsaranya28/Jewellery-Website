<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

// Handle final registration after OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
  $name     = trim($_POST['name'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  // Check OTP was verified
  if (empty($_SESSION['registration_verified'])) {
    flashMessage('error', 'Please verify your phone or email with OTP first.');
  } elseif (!$name || !$email || !$password) {
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
      // Cleanup registration session data
      unset($_SESSION['registration_verified'], $_SESSION['registration_verified_method'], $_SESSION['registration_verified_contact'], $_SESSION['registration_otp_method'], $_SESSION['registration_otp_contact']);
      flashMessage('success', "Welcome to Jewels.com, $name! 💎");
      header('Location: ' . ($_GET['redirect'] ?? 'index.php')); exit;
    }
  }
}

$pageTitle = 'Register — ' . SITE_NAME;
include 'includes/header.php';
?>

<style>
/* ── Registration Steps ── */
.reg-steps { display:flex; justify-content:center; gap:8px; margin-bottom:32px; }
.reg-step { display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:50px; font-size:13px; font-weight:500; color:#999; background:var(--ivory-dark); transition:all .3s; }
.reg-step.active { background:linear-gradient(135deg,var(--gold),var(--gold-dark)); color:#fff; box-shadow:0 4px 15px rgba(193,158,67,.35); }
.reg-step.done { background:#e8f5e9; color:#2e7d32; }
.reg-step .step-num { width:24px; height:24px; border-radius:50%; background:rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }
.reg-step.active .step-num { background:rgba(255,255,255,.3); }
.reg-step.done .step-num { background:#2e7d32; color:#fff; }

/* ── OTP Method Picker ── */
.otp-methods { display:flex; gap:12px; margin:20px 0; }
.otp-method { flex:1; padding:18px 14px; border:2px solid #e0e0e0; border-radius:var(--radius); cursor:pointer; text-align:center; transition:all .3s; background:#fafafa; }
.otp-method:hover { border-color:var(--gold); background:var(--gold-pale); }
.otp-method.selected { border-color:var(--gold); background:var(--gold-pale); box-shadow:0 4px 15px rgba(193,158,67,.2); }
.otp-method i { font-size:28px; color:var(--gold); margin-bottom:8px; display:block; }
.otp-method .method-label { font-weight:600; font-size:14px; color:var(--charcoal); }
.otp-method .method-desc { font-size:11px; color:#888; margin-top:4px; }

/* ── OTP Input Group ── */
.otp-digits { display:flex; gap:10px; justify-content:center; margin:24px 0; }
.otp-digits input { width:48px; height:56px; text-align:center; font-size:22px; font-weight:700; border:2px solid #ddd; border-radius:var(--radius-sm); background:#fafafa; transition:all .3s; color:var(--charcoal); }
.otp-digits input:focus { border-color:var(--gold); background:#fff; box-shadow:0 0 0 3px rgba(193,158,67,.15); outline:none; }

/* ── Panels ── */
.reg-panel { display:none; animation:fadeUp .4s ease; }
.reg-panel.active { display:block; }
@keyframes fadeUp { from { opacity:0; transform:translateY(15px); } to { opacity:1; transform:translateY(0); } }

/* ── Timer ── */
.otp-timer { text-align:center; font-size:13px; color:#888; margin-top:12px; }
.otp-timer strong { color:var(--gold-dark); }
.resend-btn { background:none; border:none; color:var(--gold-dark); font-weight:600; cursor:pointer; text-decoration:underline; font-size:13px; }
.resend-btn:disabled { color:#ccc; cursor:not-allowed; text-decoration:none; }

.dev-otp-box { background:#fff3cd; border:1px solid #ffc107; border-radius:var(--radius-sm); padding:10px 14px; margin-top:16px; font-size:12px; color:#856404; text-align:center; }
</style>

<section style="min-height:70vh;display:flex;align-items:center;background:linear-gradient(135deg,var(--ivory),var(--ivory-dark));">
  <div class="container">
    <div class="form-card" style="max-width:540px;">
      <div style="text-align:center;margin-bottom:8px;">
        <div style="font-size:42px;margin-bottom:8px;">💎</div>
        <h2>Create Account</h2>
        <p>Join Jewels.com and explore the finest jewellery</p>
      </div>

      <!-- Step Indicators -->
      <div class="reg-steps">
        <div class="reg-step active" id="stepInd1"><span class="step-num">1</span> Details</div>
        <div class="reg-step" id="stepInd2"><span class="step-num">2</span> Verify</div>
        <div class="reg-step" id="stepInd3"><span class="step-num">3</span> Done</div>
      </div>

      <!-- ══════ STEP 1: User Details ══════ -->
      <div class="reg-panel active" id="panelDetails">
        <button type="button" class="btn btn-google btn-full" style="margin-bottom:20px;" onclick="continueWithGoogle()">
          <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:18px;margin-right:10px;vertical-align:middle;">
          Continue with Google
        </button>
        <div class="form-divider"><span>or register manually</span></div>

        <div class="form-row">
          <div class="form-group"><label>Full Name *</label><input type="text" id="regName" placeholder="Your full name" required/></div>
          <div class="form-group"><label>Phone</label><input type="tel" id="regPhone" placeholder="10-digit mobile" maxlength="10"/></div>
        </div>
        <div class="form-group"><label>Email Address *</label><input type="email" id="regEmail" placeholder="you@email.com" required/></div>
        <div class="form-row">
          <div class="form-group"><label>Password *</label><input type="password" id="regPass" placeholder="Min 6 characters" required/></div>
          <div class="form-group"><label>Confirm Password *</label><input type="password" id="regConfirm" placeholder="Re-enter password" required/></div>
        </div>

        <!-- OTP Method Picker -->
        <label style="font-weight:600;margin-top:12px;display:block;">Verify via *</label>
        <div class="otp-methods">
          <div class="otp-method selected" data-method="email" onclick="selectMethod(this)">
            <i class="fas fa-envelope"></i>
            <div class="method-label">Email OTP</div>
            <div class="method-desc">Code sent to your email</div>
          </div>
          <div class="otp-method" data-method="phone" onclick="selectMethod(this)">
            <i class="fas fa-mobile-screen-button"></i>
            <div class="method-label">Phone OTP</div>
            <div class="method-desc">Code sent via SMS</div>
          </div>
        </div>

        <button type="button" class="btn btn-gold btn-full" style="margin-top:8px;" onclick="sendOtp()">
          <i class="fas fa-paper-plane"></i> Send OTP & Verify
        </button>
      </div>

      <!-- ══════ STEP 2: OTP Verification ══════ -->
      <div class="reg-panel" id="panelOtp">
        <div style="text-align:center;margin-bottom:20px;">
          <div id="otpIconWrapper" style="width:80px;height:80px;background:var(--ivory-dark);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:40px;position:relative;border:2px solid var(--gold-pale);">
            <span id="otpIcon">📧</span>
            <div id="gmailBadge" style="display:none;position:absolute;bottom:-5px;right:-5px;background:#fff;border-radius:50%;padding:4px;box-shadow:0 2px 5px rgba(0,0,0,0.2);">
              <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="G" style="width:16px;">
            </div>
          </div>
          <h3 id="otpHeader">Verify Email</h3>
          <p style="font-size:14px;color:#666;" id="otpSentMsg">OTP sent to your email</p>
        </div>

        <div class="otp-digits" id="otpDigits">
          <input type="text" maxlength="1" inputmode="numeric" autocomplete="one-time-code"/>
          <input type="text" maxlength="1" inputmode="numeric"/>
          <input type="text" maxlength="1" inputmode="numeric"/>
          <input type="text" maxlength="1" inputmode="numeric"/>
          <input type="text" maxlength="1" inputmode="numeric"/>
          <input type="text" maxlength="1" inputmode="numeric"/>
        </div>

        <button type="button" class="btn btn-gold btn-full" onclick="verifyOtp()" id="btnVerify">
          <i class="fas fa-check-circle"></i> Verify OTP
        </button>

        <div class="otp-timer" id="otpTimer">
          Resend OTP in <strong id="countdown">60</strong>s
        </div>
        <div style="text-align:center;margin-top:8px;">
          <button type="button" class="resend-btn" id="btnResend" disabled onclick="sendOtp()">Resend OTP</button>
          <span style="margin:0 8px;color:#ddd;">|</span>
          <button type="button" class="resend-btn" onclick="goBack()" style="color:#888;">← Change details</button>
        </div>

        <div class="dev-otp-box" id="devOtpBox" style="display:none;">
          <strong>🔧 Dev Mode:</strong> Your OTP is <span id="devOtpCode" style="font-size:18px;font-weight:700;letter-spacing:2px;">------</span>
        </div>
      </div>

      <!-- ══════ STEP 3: Creating Account (auto-submits) ══════ -->
      <div class="reg-panel" id="panelDone">
        <form method="POST" id="finalRegForm">
          <input type="hidden" name="register" value="1"/>
          <input type="hidden" name="name" id="fName"/>
          <input type="hidden" name="email" id="fEmail"/>
          <input type="hidden" name="phone" id="fPhone"/>
          <input type="hidden" name="password" id="fPass"/>
          <input type="hidden" name="confirm" id="fConfirm"/>
        </form>
        <div style="text-align:center;padding:30px 0;">
          <div style="font-size:48px;margin-bottom:12px;">✅</div>
          <h3 style="color:var(--gold-dark);">Verified Successfully!</h3>
          <p style="color:#888;">Creating your account…</p>
          <div style="margin-top:16px;"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--gold);"></i></div>
        </div>
      </div>

        <div class="form-link">Already have an account? <a href="login.php">Login here</a></div>
      </div>
    </div>
  </div>
</section>

<style>
/* ── Google Button Style ── */
.btn-google {
  background: #fff;
  color: #757575;
  border: 1px solid #ddd;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}
.btn-google:hover {
  background: #f8f8f8;
  border-color: #ccc;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transform: translateY(-1px);
}
</style>

<script>
function continueWithGoogle() {
  showToast('Connecting to Google...', 'info');
  setTimeout(() => {
    // Mock Google Login Popup / Logic
    if (confirm('Verify with Google: jewellryshop@gmail.com?')) {
      showToast('Google Account Verified!', 'success');
      setStep(3);
      // Mock data from Google
      document.getElementById('regName').value = "Google User";
      document.getElementById('regEmail').value = "jewellryshop@gmail.com";
      document.getElementById('regPass').value = "google_auth_placeholder";
      document.getElementById('regConfirm').value = "google_auth_placeholder";
      
      // Auto-submit
      document.getElementById('fName').value = "Google User";
      document.getElementById('fEmail').value = "jewellryshop@gmail.com";
      document.getElementById('fPhone').value = "";
      document.getElementById('fPass').value = "google_auth_placeholder";
      document.getElementById('fConfirm').value = "google_auth_placeholder";
      setTimeout(() => document.getElementById('finalRegForm').submit(), 1200);
    }
  }, 1000);
}
</script>

<script>
let selectedMethod = 'email';
let countdownInterval = null;

function selectMethod(el) {
  document.querySelectorAll('.otp-method').forEach(m => m.classList.remove('selected'));
  el.classList.add('selected');
  selectedMethod = el.dataset.method;
}

function setStep(n) {
  document.querySelectorAll('.reg-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.reg-step').forEach(s => s.classList.remove('active','done'));
  for (let i = 1; i < n; i++) {
    document.getElementById('stepInd'+i).classList.add('done');
    document.getElementById('stepInd'+i).querySelector('.step-num').innerHTML = '<i class="fas fa-check" style="font-size:10px;"></i>';
  }
  document.getElementById('stepInd'+n).classList.add('active');
  if (n===1) document.getElementById('panelDetails').classList.add('active');
  if (n===2) document.getElementById('panelOtp').classList.add('active');
  if (n===3) document.getElementById('panelDone').classList.add('active');
}

function goBack() {
  clearInterval(countdownInterval);
  setStep(1);
}

function validateStep1() {
  const name = document.getElementById('regName').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const phone = document.getElementById('regPhone').value.trim();
  const pass = document.getElementById('regPass').value;
  const conf = document.getElementById('regConfirm').value;
  if (!name) { showToast('Please enter your name.','error'); return false; }
  if (!email || !email.includes('@')) { showToast('Please enter a valid email.','error'); return false; }
  if (pass.length < 6) { showToast('Password must be at least 6 characters.','error'); return false; }
  if (pass !== conf) { showToast('Passwords do not match.','error'); return false; }
  if (selectedMethod === 'phone' && !/^[6-9]\d{9}$/.test(phone)) {
    showToast('Please enter a valid 10-digit phone number.','error'); return false;
  }
  return true;
}

async function sendOtp() {
  if (!validateStep1()) return;

  const btn = event.target.closest('button');
  const origHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
  btn.disabled = true;

  const fd = new FormData();
  fd.append('method', selectedMethod);
  fd.append('email', document.getElementById('regEmail').value.trim());
  fd.append('phone', document.getElementById('regPhone').value.trim());

  try {
    const res = await fetch('ajax/send-otp.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      if (data.smtp_error) console.error("SMTP Error:", data.smtp_error);
      showToast(data.message, data.smtp_error ? 'info' : 'success');

      // Update OTP panel UI
      const emailVal = document.getElementById('regEmail').value.trim();
      const isGmail = emailVal.toLowerCase().endsWith('@gmail.com');
      
      document.getElementById('otpIcon').textContent = selectedMethod==='email' ? '📧' : '📱';
      document.getElementById('otpHeader').textContent = selectedMethod==='email' ? 'Verify Email' : 'Verify Phone';
      document.getElementById('otpSentMsg').textContent = data.message;
      document.getElementById('gmailBadge').style.display = (selectedMethod==='email' && isGmail) ? 'block' : 'none';

      // Dev OTP hint
      if (data.dev_otp) {
        document.getElementById('devOtpBox').style.display = 'block';
        document.getElementById('devOtpCode').textContent = data.dev_otp;
      } else {
        document.getElementById('devOtpBox').style.display = 'none';
      }
      setStep(2);
      startCountdown();
      // Focus first OTP digit
      document.querySelector('#otpDigits input').focus();
    } else {
      showToast(data.message, 'error');
    }
  } catch(e) {
    showToast('Network error. Please try again.','error');
  }
  btn.innerHTML = origHtml;
  btn.disabled = false;
}

function startCountdown() {
  let sec = 60;
  const el = document.getElementById('countdown');
  const timerDiv = document.getElementById('otpTimer');
  const resendBtn = document.getElementById('btnResend');
  resendBtn.disabled = true;
  timerDiv.style.display = 'block';
  clearInterval(countdownInterval);
  countdownInterval = setInterval(() => {
    sec--;
    el.textContent = sec;
    if (sec <= 0) {
      clearInterval(countdownInterval);
      timerDiv.style.display = 'none';
      resendBtn.disabled = false;
    }
  }, 1000);
}

async function verifyOtp() {
  const inputs = document.querySelectorAll('#otpDigits input');
  let otp = '';
  inputs.forEach(i => otp += i.value);
  if (otp.length !== 6) { showToast('Please enter the complete 6-digit OTP.','error'); return; }

  const btn = document.getElementById('btnVerify');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying…';
  btn.disabled = true;

  const fd = new FormData();
  fd.append('otp', otp);

  try {
    const res = await fetch('ajax/verify-otp.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      clearInterval(countdownInterval);
      setStep(3);
      // Auto-submit the registration form
      document.getElementById('fName').value = document.getElementById('regName').value.trim();
      document.getElementById('fEmail').value = document.getElementById('regEmail').value.trim();
      document.getElementById('fPhone').value = document.getElementById('regPhone').value.trim();
      document.getElementById('fPass').value = document.getElementById('regPass').value;
      document.getElementById('fConfirm').value = document.getElementById('regConfirm').value;
      setTimeout(() => document.getElementById('finalRegForm').submit(), 1200);
    } else {
      showToast(data.message, 'error');
      btn.innerHTML = '<i class="fas fa-check-circle"></i> Verify OTP';
      btn.disabled = false;
    }
  } catch(e) {
    showToast('Network error.','error');
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Verify OTP';
    btn.disabled = false;
  }
}

// ── OTP digit auto-jump logic ──
document.querySelectorAll('#otpDigits input').forEach((inp, idx, arr) => {
  inp.addEventListener('input', () => {
    inp.value = inp.value.replace(/\D/g,'');
    if (inp.value && idx < arr.length - 1) arr[idx+1].focus();
  });
  inp.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !inp.value && idx > 0) arr[idx-1].focus();
  });
  inp.addEventListener('paste', (e) => {
    e.preventDefault();
    const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g,'').slice(0,6);
    pasted.split('').forEach((ch,i) => { if (arr[i]) arr[i].value = ch; });
    if (arr[pasted.length-1]) arr[pasted.length-1].focus();
  });
});
</script>

<?php include 'includes/footer.php'; ?>
