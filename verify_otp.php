<?php
// Set timezone to UTC for consistency with otp_expires comparisons
date_default_timezone_set('UTC');

// Force session to use cookies
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
session_set_cookie_params(0, '/', '', false, true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Connection.php';

header('Content-Type: application/json');

$email    = trim($_POST['email'] ?? '');
$otp_code = trim($_POST['otp_code'] ?? '');

// Fall back to the session email if none was posted
if (empty($email) && isset($_SESSION['signup_email'])) {
    $email = $_SESSION['signup_email'];
}

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is missing. Please register again.']);
    exit();
}

if (empty($otp_code) || !preg_match('/^[0-9]{6}$/', $otp_code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit verification code.']);
    exit();
}

// Single query: matches only if email + code are correct, code hasn't
// expired (per MySQL's own clock), and the account isn't already verified.
$stmt = $conn->prepare("
    SELECT id, username
    FROM users
    WHERE email = ?
      AND otp_code = ?
      AND otp_expires > UTC_TIMESTAMP()
      AND is_verified = 0
    LIMIT 1
");
$stmt->bind_param("ss", $email, $otp_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();

    // Figure out which specific reason it failed, for a clearer message
    $check = $conn->prepare("SELECT otp_code, otp_expires, is_verified FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $userData = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$userData) {
        echo json_encode(['success' => false, 'message' => 'Account not found. Please register first.']);
        exit();
    }
    if ((int)$userData['is_verified'] === 1) {
        echo json_encode(['success' => false, 'message' => 'This account is already verified. Please login.']);
        exit();
    }
    if (empty($userData['otp_code']) || empty($userData['otp_expires'])) {
        echo json_encode(['success' => false, 'message' => 'No active code found. Please request a new OTP.']);
        exit();
    }

    $check2 = $conn->prepare("SELECT (otp_expires > UTC_TIMESTAMP()) AS still_valid FROM users WHERE email = ?");
    $check2->bind_param("s", $email);
    $check2->execute();
    $validity = $check2->get_result()->fetch_assoc();
    $check2->close();

    if ((int)$validity['still_valid'] !== 1) {
        echo json_encode(['success' => false, 'message' => 'This code has expired. Please request a new one.']);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Incorrect code. Please check and try again.']);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Correct code — finalize the account
$update = $conn->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?");
$update->bind_param("i", $user['id']);

if ($update->execute()) {
    unset($_SESSION['signup_step'], $_SESSION['signup_email'], $_SESSION['signup_username']);
    $_SESSION['temp_username'] = $user['username'];

    echo json_encode([
        'success'  => true,
        'message'  => 'Account verified successfully!',
        'redirect' => 'Login.php'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $update->error]);
}

$update->close();
$conn->close();
