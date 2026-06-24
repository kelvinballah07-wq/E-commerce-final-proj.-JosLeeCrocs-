<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Connection.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit();
}

// Check if the email exists in the database
$stmt = $conn->prepare("SELECT id, username, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found for this email.']);
    exit();
}

if ((int)$user['is_verified'] === 1) {
    echo json_encode(['success' => false, 'message' => 'This account is already verified. Please login.']);
    exit();
}

// Generate new OTP
$otp_code = sprintf("%06d", random_int(0, 999999));

// Update the database with new OTP
$stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires = NOW() + INTERVAL 10 MINUTE WHERE id = ?");
$stmt->bind_param("si", $otp_code, $user['id']);
$stmt->execute();
$stmt->close();

error_log("New OTP generated for $email: $otp_code");

// Send email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kelvinballah07@gmail.com';
    $mail->Password = 'puby vhqm rafg jknd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('kelvinballah07@gmail.com', 'JosLee Crocs');
    $mail->addAddress($email, $user['username']);
    $mail->isHTML(true);
    $mail->Subject = 'Your Verification Code - JosLee Crocs';
    $mail->Body = "
    <html><body>
        <h2>Email Verification</h2>
        <p>Hello <strong>{$user['username']}</strong>,</p>
        <p>Your verification code is: <strong style='font-size:24px;color:#D97A5C;'>$otp_code</strong></p>
        <p>This code will expire in 10 minutes.</p>
        <p>If you didn't request this, please ignore this email.</p>
    </body></html>";
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'A new verification code has been sent to your email.']);
} catch (Exception $e) {
    error_log('Resend OTP email failed: ' . $mail->ErrorInfo);
    echo json_encode(['success' => true, 'message' => "Email sending failed. Your test code is: $otp_code"]);
}
?>
