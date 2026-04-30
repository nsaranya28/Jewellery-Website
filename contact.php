<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Handle Form Submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $subject && $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            flashMessage('success', 'Thank you! Your message has been received.');
            header("Location: contact.php");
            exit;
        } catch (PDOException $e) {
            flashMessage('error', 'Something went wrong. Please try again later.');
        }
    } else {
        flashMessage('error', 'Please fill in all fields.');
    }
}

$pageTitle = "Contact Us — Jewels.com";
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── CONTACT HERO ────────────────────────────────────────── -->
<section class="page-header">
  <div class="container">
    <h1>Connect with Us</h1>
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>/">Home</a> <i class="fas fa-chevron-right"></i> <span>Contact Us</span>
    </div>
  </div>
</section>

<!-- ── CONTACT CONTENT ─────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="shop-layout">
      
      <!-- Contact Info Cards -->
      <div class="filter-sidebar">
        <h3>Visit Our Boutique</h3>
        
        <div class="contact-info-list" style="margin-top: 20px;">
          <div class="contact-item" style="margin-bottom: 30px; display: flex; gap: 15px;">
            <div style="width: 48px; height: 48px; background: var(--gold-pale); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--gold-dark); font-size: 20px; flex-shrink: 0;">
              <i class="fas fa-location-dot"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-serif); font-size: 18px; color: var(--dark); margin-bottom: 5px;">Our Location</h4>
              <p style="font-size: 14px; color: var(--gray); line-height: 1.6;">45, Golden Avenue, T. Nagar,<br>Chennai, Tamil Nadu - 600017</p>
            </div>
          </div>

          <div class="contact-item" style="margin-bottom: 30px; display: flex; gap: 15px;">
            <div style="width: 48px; height: 48px; background: var(--gold-pale); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--gold-dark); font-size: 20px; flex-shrink: 0;">
              <i class="fas fa-phone"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-serif); font-size: 18px; color: var(--dark); margin-bottom: 5px;">Call Us</h4>
              <p style="font-size: 14px; color: var(--gray);">Primary: +91 98765 43210</p>
              <p style="font-size: 14px; color: var(--gray);">Support: +91 98765 01234</p>
            </div>
          </div>

          <div class="contact-item" style="margin-bottom: 30px; display: flex; gap: 15px;">
            <div style="width: 48px; height: 48px; background: var(--gold-pale); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--gold-dark); font-size: 20px; flex-shrink: 0;">
              <i class="fas fa-envelope"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-serif); font-size: 18px; color: var(--dark); margin-bottom: 5px;">Email Support</h4>
              <p style="font-size: 14px; color: var(--gray);">support@jewels.com</p>
              <p style="font-size: 14px; color: var(--gray);">inquiries@jewels.com</p>
            </div>
          </div>

          <div class="contact-item" style="display: flex; gap: 15px;">
            <div style="width: 48px; height: 48px; background: var(--gold-pale); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--gold-dark); font-size: 20px; flex-shrink: 0;">
              <i class="fas fa-clock"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-serif); font-size: 18px; color: var(--dark); margin-bottom: 5px;">Business Hours</h4>
              <p style="font-size: 14px; color: var(--gray);">Mon - Sat: 10:00 AM - 8:30 PM</p>
              <p style="font-size: 14px; color: var(--gray);">Sun: 11:00 AM - 6:00 PM</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="form-card" style="margin: 0; max-width: 100%;">
        <h2>Send a Message</h2>
        <p>Have questions about our collections or a custom order? Drop us a line.</p>
        
        <form action="" method="POST" id="contactForm">
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="name" placeholder="Enter your name" required>
            </div>
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" name="email" placeholder="you@email.com" required>
            </div>
          </div>
          
          <div class="form-group">
            <label>Subject</label>
            <select name="subject" required>
              <option value="">Select a reason</option>
              <option value="Inquiry">General Inquiry</option>
              <option value="Custom Order">Custom Jewellery Design</option>
              <option value="Order Support">Order Support</option>
              <option value="Repair">Repair & Polishing</option>
              <option value="Feedback">Feedback</option>
            </select>
          </div>

          <div class="form-group">
            <label>Your Message</label>
            <textarea name="message" rows="5" placeholder="How can we help you?" required></textarea>
          </div>

          <button type="submit" class="btn btn-gold btn-full">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>
        </form>
      </div>

    </div>

    <!-- ── MAP BELOW INFO ── -->
    <div style="margin-top: 40px; border-radius: var(--radius-lg); overflow: hidden; height: 400px; border: 1px solid var(--gold-pale); box-shadow: var(--shadow-sm);">
      <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3886.741000!2d80.231000!3d13.041000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a52665e!2sT.+Nagar%2C+Chennai%2C+Tamil+Nadu!5e0!3m2!1sen!2sin!4v1!5m2!1sen!2sin" 
        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
      </iframe>
    </div>
  </div>
</section>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
