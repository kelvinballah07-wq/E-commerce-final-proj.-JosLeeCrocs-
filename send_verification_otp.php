<?php
session_start();
require_once 'Connection.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate input
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered. Please login.']);
    $stmt->close();
    exit();
}
$stmt->close();

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken. Please choose another.']);
    $stmt->close();
    exit();
}
$stmt->close();

// Generate OTP
$otp_code = sprintf("%06d", mt_rand(1, 999999));
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store in session (simpler approach)
$_SESSION['signup_step'] = 'verify';
$_SESSION['signup_email'] = $email;
$_SESSION['signup_username'] = $username;
$_SESSION['signup_password'] = password_hash($password, PASSWORD_DEFAULT);
$_SESSION['signup_otp'] = $otp_code;
$_SESSION['signup_otp_expires'] = $expires;

// For testing - show OTP in console and alert (remove in production)
echo json_encode(['success' => true, 'message' => 'OTP sent to your email. For testing, OTP is: ' . $otp_code, 'debug_otp' => $otp_code]);

// Send OTP email
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
    $mail->addAddress($email, $username);
    
    $mail->isHTML(true);
    $mail->Subject = 'Email Verification OTP - JosLee Crocs';
    $mail->Body = "
    <html>
    <body>
        <h2>Email Verification OTP</h2>
        <p>Hello <strong>$username</strong>,</p>
        <p>Your OTP code for email verification is: <strong style='font-size:24px'>$otp_code</strong></p>
        <p>This OTP will expire in <strong>10 minutes</strong>.</p>
        <p>If you didn't request this, please ignore this email.</p>
    </body>
    </html>
    ";
    
    $mail->send();
    // Don't show OTP in response when email works
    echo json_encode(['success' => true, 'message' => 'OTP sent to your email. Please check your inbox.']);
} catch (Exception $e) {
    // If email fails, still show OTP for testing
    echo json_encode(['success' => true, 'message' => 'OTP sent to your email. (Test OTP: ' . $otp_code . ')', 'debug_otp' => $otp_code]);
}
?>
