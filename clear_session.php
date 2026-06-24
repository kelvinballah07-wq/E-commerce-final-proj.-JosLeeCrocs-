<?php
session_start();
session_unset();
session_destroy();

// Also try to clear cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

echo "Session cleared! <a href='Login.php'>Go to Login Page</a>";
?>
