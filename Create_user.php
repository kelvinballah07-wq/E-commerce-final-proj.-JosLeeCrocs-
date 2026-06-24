<?php
require_once 'Connection.php';

// Check if admin user exists
$check = $conn->query("SELECT * FROM users WHERE email = 'admin@gmail.com'");
if ($check->num_rows == 0) {
    // Create admin user
    $username = "admin";
    $email = "admin@gmail.com";
    $password = "200600";
    
    $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'admin')";
    if ($conn->query($sql)) {
        echo "✅ Admin user created successfully!<br>";
        echo "Email: admin@gmail.com<br>";
        echo "Password: 200600";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Admin user already exists!<br>";
    echo "Email: admin@gmail.com<br>";
    echo "Password: 200600";
}
?>
