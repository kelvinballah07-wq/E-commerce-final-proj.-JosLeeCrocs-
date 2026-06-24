<?php
session_start();

header('Content-Type: application/json');

// Clear signup-related session data
unset($_SESSION['signup_step']);
unset($_SESSION['signup_email']);
unset($_SESSION['signup_username']);
unset($_SESSION['signup_password']);

echo json_encode(['success' => true, 'message' => 'Session cleared']);
?>
