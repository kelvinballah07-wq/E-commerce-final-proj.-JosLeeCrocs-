<?php
require_once 'Connection.php';

// Set new password
$new_password = '200600';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ? WHERE email = 'admin@gmail.com'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "✅ Password has been reset successfully!<br><br>";
    echo "<strong>New Login Credentials:</strong><br>";
    echo "Email: admin@gmail.com<br>";
    echo "Password: 200600<br><br>";
    echo "<a href='Login.php'>Go to Login Page</a>";
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
