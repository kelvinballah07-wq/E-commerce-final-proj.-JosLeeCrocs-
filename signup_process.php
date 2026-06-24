<?php
// Force session to use cookies
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
session_set_cookie_params(0, '/', '', false, true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to UTC for consistency with verify_otp.php
date_default_timezone_set('UTC');

require_once 'Connection.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

/* ---------------------------------------------------------
   Helper: send the OTP email
   --------------------------------------------------------- */
function sendOtpEmail($email, $username, $otp_code, $heading = 'Email Verification OTP') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kelvinballah07@gmail.com';
        $mail->Password = 'puby vhqm rafg jknd'; // ⚠️ rotate this app password — see note below
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('kelvinballah07@gmail.com', 'JosLee Crocs');
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = $heading . ' - JosLee Crocs';
        $mail->Body = "
        <html><body>
            <h2>$heading</h2>
            <p>Hello <strong>$username</strong>,</p>
            <p>Your verification code is: <strong style='font-size:24px;color:#D97A5C;'>$otp_code</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
        </body></html>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('OTP email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/* ===========================================================
   ACTION: send_otp — Step 1 of signup
   Writes the pending user + OTP directly into the users table,
   using MySQL's own UTC_TIMESTAMP() for the expiry so there is
   no PHP/MySQL clock-skew risk.
   =========================================================== */
if ($action === 'send_otp') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

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
    if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores.']);
        exit();
    }

    // Already a verified account on this email?
    $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing && (int)$existing['is_verified'] === 1) {
        echo json_encode(['success' => false, 'message' => 'Email already registered. Please login.']);
        exit();
    }

    // Username taken by a different email?
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email != ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already taken.']);
        $stmt->close();
        exit();
    }
    $stmt->close();

    $otp_code        = sprintf("%06d", random_int(0, 999999));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($existing) {
        // Unverified row already exists for this email — overwrite with fresh details
        $stmt = $conn->prepare(
            "UPDATE users
             SET username = ?, password = ?, otp_code = ?, otp_expires = UTC_TIMESTAMP() + INTERVAL 10 MINUTE, is_verified = 0
             WHERE email = ?"
        );
        $stmt->bind_param("ssss", $username, $hashed_password, $otp_code, $email);
    } else {
        // Brand new pending signup
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password, role, created_at, otp_code, otp_expires, is_verified)
             VALUES (?, ?, ?, 'user', UTC_TIMESTAMP(), ?, UTC_TIMESTAMP() + INTERVAL 10 MINUTE, 0)"
        );
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $otp_code);
    }

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Session only tracks which email is mid-verification
    $_SESSION['signup_step']     = 'verify';
    $_SESSION['signup_email']    = $email;
    $_SESSION['signup_username'] = $username;

    if (sendOtpEmail($email, $username, $otp_code)) {
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email!']);
    } else {
        echo json_encode(['success' => true, 'message' => "Email could not be sent. Test code: $otp_code"]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
