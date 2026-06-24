<?php
session_start();

if (!isset($_SESSION['signup_email'])) {
    header("Location: CreateAccount.php");
    exit();
}

// Generate new OTP
$new_otp = sprintf("%06d", mt_rand(1, 999999));
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$_SESSION['signup_otp'] = $new_otp;
$_SESSION['signup_otp_expires'] = $expires;

// Try to send email
if (file_exists('PHPMailer/src/PHPMailer.php')) {
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kelvinballah07@gmail.com';
        $mail->Password   = 'puby vhqm rafg jknd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('kelvinballah07@gmail.com', 'JosLee Crocs');
        $mail->addAddress($_SESSION['signup_email'], $_SESSION['signup_username']);
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification OTP - JosLee Crocs';
        $mail->Body = "<h2>Email Verification OTP</h2><p>Hello " . $_SESSION['signup_username'] . ",</p><p>Your new OTP code is: <strong style='font-size:24px'>$new_otp</strong></p><p>This OTP will expire in 10 minutes.</p>";
        $mail->send();
    } catch (Exception $e) {
        // Email failed
    }
}

header("Location: CreateAccount.php?step=verify");
exit();
?>
