<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'Connection.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter email and password']);
        exit();
    }

    // Check if user exists in DB by email — now also fetching is_verified
    $stmt = $conn->prepare("SELECT id, username, password, role, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            // Block unverified REGULAR users — but never block admins,
            // since admin accounts are created directly and don't go
            // through the OTP signup flow.
            if ($user['role'] !== 'admin' && (int)$user['is_verified'] !== 1) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Please verify your email before logging in. Check your inbox for the OTP code.'
                ]);
                $stmt->close();
                $conn->close();
                exit();
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $user['role'];
            $_SESSION['isLoggedIn'] = true;

            // Determine redirect based on role
            if ($user['role'] === 'admin') {
                $redirect = 'admin_dashboard.php';
            } else {
                $redirect = 'Dashboard.php';
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'role' => $user['role'],
                'redirect' => $redirect
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No account found with this email']);
        exit();
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
$conn->close();
?>
