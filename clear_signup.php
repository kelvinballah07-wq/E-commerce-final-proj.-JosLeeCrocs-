<?php
session_start();
unset($_SESSION['signup_email']);
unset($_SESSION['signup_username']);
unset($_SESSION['signup_password']);
unset($_SESSION['signup_otp']);
unset($_SESSION['signup_otp_expires']);
header("Location: CreateAccount.php");
exit();
?>
