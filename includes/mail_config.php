<?php
/**
 * SMTP Configuration for Jewels.com
 * Update these settings to enable real email sending.
 */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // Use 465 for SSL (Recommended for PHPMailerLite)
define('SMTP_USER', 'nsaranya282@gmail.com');

/**
 * !!! IMPORTANT: Gmail App Password Required !!!
 * 1. Enable 2-Step Verification in your Google Account.
 * 2. Go to: https://myaccount.google.com/apppasswords
 * 3. Create a new App Password for "Mail" and "Other (Custom name: Jewels Website)".
 * 4. Paste the 16-character code below.
 */
define('SMTP_PASS', ''); 

define('SMTP_FROM', 'nsaranya282@gmail.com');
define('SMTP_NAME', 'Jewels.com Support');
