<?php
header('Content-Type: application/json');

// Force session to use cookies
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Connection.php';

// Get POST data
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate input
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required.'
    ]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit();
}

// Validate password length
if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters long.'
    ]);
    exit();
}

// Check if email already exists
$check_sql = "SELECT id FROM users WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Email already registered. Please use a different email or login.'
    ]);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// Check if username already exists
$check_username_sql = "SELECT id FROM users WHERE username = ?";
$check_username_stmt = $conn->prepare($check_username_sql);
$check_username_stmt->bind_param("s", $username);
$check_username_stmt->execute();
$check_username_result = $check_username_stmt->get_result();

if ($check_username_result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Username already taken. Please choose another username.'
    ]);
    $check_username_stmt->close();
    $conn->close();
    exit();
}
$check_username_stmt->close();

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$insert_sql = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("sss", $username, $email, $hashed_password);

if ($insert_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Please login.',
        'redirect' => 'Login.php'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create account. Please try again.'
    ]);
}

$insert_stmt->close();
$conn->close();
?>
