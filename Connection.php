<?php
// Set timezone to Rwanda/Kigali (East African Time)
date_default_timezone_set('Africa/Kigali');

// Rest of your code...
error_reporting(0); // Turn off errors for production

// InfinityFree Database Configuration - UPDATED
$servername = "sql208.infinityfree.com";   // ← YOUR CORRECT HOSTNAME
$db_username = "if0_41992639";              // ← YOUR USERNAME
$db_password = "j0LtpotNeqti";        // ← YOUR INFINITYFREE PASSWORD
$dbname = "if0_41992639_project1";          // ← YOUR DATABASE NAME

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
