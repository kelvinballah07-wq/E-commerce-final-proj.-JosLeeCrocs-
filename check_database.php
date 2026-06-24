<?php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "project1";

echo "<h2>Database Connection Test</h2>";

// Test connection
$conn = new mysqli($servername, $db_username, $db_password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✅ Connected to MySQL successfully!<br>";

// Check if database exists
$result = $conn->query("SHOW DATABASES LIKE '$dbname'");
if ($result->num_rows > 0) {
    echo "✅ Database '$dbname' exists!<br>";
    
    // Select database
    $conn->select_db($dbname);
    
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ Table 'users' exists!<br>";
        
        // Show table structure
        $result = $conn->query("DESCRIBE users");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count existing users
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "<p>📊 Total users in database: <strong>" . $row['count'] . "</strong></p>";
        
        // Show all users (without passwords)
        $result = $conn->query("SELECT id, username, created_at FROM users");
        if ($result->num_rows > 0) {
            echo "<h3>Registered Users:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Created At</th></tr>";
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No users registered yet.</p>";
        }
    } else {
        echo "❌ Table 'users' does not exist!<br>";
        echo "<p>Please run the following SQL to create the table:</p>";
        echo "<pre>
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
        </pre>";
    }
} else {
    echo "❌ Database '$dbname' does not exist!<br>";
    echo "<p>Please run the following SQL to create the database:</p>";
    echo "<pre>CREATE DATABASE project1;</pre>";
}

$conn->close();
?>
