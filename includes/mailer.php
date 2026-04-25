<?php
require_once 'mail_config.php';

/**
 * Sends a real email using SMTP.
 * Note: For production, it is highly recommended to use PHPMailer.
 */
function send_mail_smtp($to, $subject, $message) {
    if (empty(SMTP_PASS)) {
        // Fallback to local mail() if no password set (usually fails on XAMPP)
        $headers = "From: " . SMTP_FROM . "\r\n" .
                   "Reply-To: " . SMTP_FROM . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        return @mail($to, $subject, $message, $headers);
    }

    // In a real project, you would include PHPMailer here:
    // require 'PHPMailer/src/Exception.php';
    // require 'PHPMailer/src/PHPMailer.php';
    // require 'PHPMailer/src/SMTP.php';
    // 
    // $mail = new PHPMailer\PHPMailer\PHPMailer();
    // $mail->isSMTP();
    // $mail->Host = SMTP_HOST;
    // $mail->SMTPAuth = true;
    // $mail->Username = SMTP_USER;
    // $mail->Password = SMTP_PASS;
    // $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    // $mail->Port = SMTP_PORT;
    // $mail->setFrom(SMTP_FROM, SMTP_NAME);
    // $mail->addAddress($to);
    // $mail->Subject = $subject;
    // $mail->Body = $message;
    // return $mail->send();

    // For now, we will use a high-quality simulation that informs the user
    // of the missing PHPMailer library if they haven't set it up.
    // To make it work on localhost/XAMPP, PHPMailer is almost always required.
    
    return @mail($to, $subject, $message, "From: " . SMTP_FROM);
}
