<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Connection.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$message = '';
$message_type = '';

// Function to send OTP email
function sendOTPEmail($to_email, $to_name, $otp_code) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kelvinballah07@gmail.com'; // YOUR GMAIL ADDRESS
        $mail->Password   = 'puby vhqm rafg jknd';      // YOUR GMAIL APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('kelvinballah07@gmail.com', 'JosLee Crocs');
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - JosLee Crocs';
        
        // Email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #D97A5C 0%, #8B3A3A 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; text-align: center; }
                .otp-code { font-size: 32px; font-weight: bold; color: #D97A5C; letter-spacing: 5px; padding: 15px; background: white; border-radius: 10px; display: inline-block; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .warning { color: #e74c3c; font-size: 12px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔐 Password Reset OTP</h2>
                </div>
                <div class='content'>
                    <p>Hello <strong>$to_name</strong>,</p>
                    <p>We received a request to reset your password for your JosLee Crocs account.</p>
                    <p>Use the following One-Time Password (OTP) to reset your password:</p>
                    <div class='otp-code'>$otp_code</div>
                    <p>This OTP will expire in <strong>10 minutes</strong>.</p>
                    <p>If you didn't request this, you can safely ignore this email.</p>
                    <hr>
                    <p class='warning'>⚠️ Never share this OTP with anyone.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 JosLee Crocs. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Hello $to_name,\n\nWe received a request to reset your password for your JosLee Crocs account.\n\nYour OTP code is: $otp_code\n\nThis OTP will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nJosLee Crocs Team";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $e->getMessage());
        return false;
    }
}

// Generate random OTP
function generateOTP() {
    return sprintf("%06d", mt_rand(1, 999999));
}

// Check if the users table has reset_token and reset_expires columns
$check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(10) NULL");
    $conn->query("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
}

// Step 1: Request OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_otp'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $otp_code = generateOTP(); // Generate real random OTP
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $update_stmt->bind_param("sss", $otp_code, $expires, $email);
            
            if ($update_stmt->execute()) {
                // Send real OTP email
                if (sendOTPEmail($email, $user['username'], $otp_code)) {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['generated_otp'] = $otp_code; // Store for debugging
                    header("Location: ForgotPassword.php?step=verify&msg=OTP+sent+to+your+email");
                    exit();
                } else {
                    $message = "Failed to send OTP email. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Error generating OTP. Please try again.";
                $message_type = "error";
            }
            $update_stmt->close();
        } else {
            $message = "Email not found in our system.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Step 2: Verify OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $otp_code = isset($_POST['otp_code']) ? $_POST['otp_code'] : '';
    $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    
    if (empty($otp_code)) {
        $message = "Please enter the OTP code.";
        $message_type = "error";
    } else {
        // Verify OTP from database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW()");
        $stmt->bind_param("ss", $email, $otp_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['otp_verified'] = true;
            header("Location: ForgotPassword.php?step=reset&msg=OTP+verified");
            exit();
        } else {
            $message = "Invalid or expired OTP. Please request a new one.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Step 3: Reset password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } elseif (strlen($new_password) < 4) {
        $message = "Password must be at least 4 characters long.";
        $message_type = "error";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);
        
        if ($update_stmt->execute()) {
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['generated_otp']);
            header("Location: Login.php?reset=success");
            exit();
        } else {
            $message = "Error resetting password. Please try again.";
            $message_type = "error";
        }
        $update_stmt->close();
    }
}

// Get message from URL if exists
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = "success";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - JosLee Crocs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --cream: #FEF7E8;
            --terracotta: #D97A5C;
            --deep-burgundy: #8B3A3A;
            --olive: #7A8B5E;
            --honey: #E8B86B;
            --soft-white: #FFFFFF;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: linear-gradient(135deg, var(--cream) 0%, #FDF2E3 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
        }
        
        header {
            background: linear-gradient(112deg, #FFFFFF 0%, #FEF4E8 100%);
            padding: 1.5rem 2rem 1rem 2rem;
            border-bottom: 3px solid var(--honey);
        }
        
        nav {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 0.75rem;
        }
        
        nav a {
            color: var(--terracotta);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        nav a:hover { color: var(--deep-burgundy); }
        
        .welcome-header {
            font-size: 1.35rem;
            color: #7A5A4B;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(232, 184, 107, 0.12);
            padding: 6px 18px;
            border-radius: 50px;
        }
        
        .content-wrapper {
            max-width: 1300px;
            margin: 1.8rem auto;
            padding: 0 28px 50px 28px;
        }
        
        .knitting-card {
            background: linear-gradient(115deg, #FFFBF4 0%, #FEF9EF 100%);
            border-radius: 56px;
            padding: 28px 20px;
            margin-bottom: 32px;
            text-align: center;
            border: 1px solid #FFE4BE;
        }
        
        .knitting-title {
            font-family: 'Georgia', serif;
            font-size: 2rem;
            color: #AC6D49;
        }
        
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0 40px;
        }
        
        .forgot-form {
            max-width: 460px;
            width: 100%;
            padding: 36px 32px 42px;
            background: var(--soft-white);
            border-radius: 48px;
            box-shadow: 0 25px 45px -12px rgba(85, 55, 35, 0.25);
            border: 1px solid #F7E2CA;
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 28px;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--deep-burgundy);
        }
        
        .form-title i { color: var(--honey); font-size: 2rem; }
        
        .subtitle {
            text-align: center;
            color: #7C6857;
            margin-bottom: 25px;
        }
        
        .forgot-form input {
            width: 100%;
            padding: 14px 16px;
            margin: 10px 0;
            border: 2px solid #F0E0CE;
            border-radius: 60px;
            font-size: 0.95rem;
            background: #FFFDF9;
        }
        
        .forgot-form input:focus {
            outline: none;
            border-color: var(--honey);
        }
        
        .otp-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: bold;
        }
        
        .password-container {
            position: relative;
            width: 100%;
        }
        
        .password-container input {
            padding-right: 50px;
        }
        
        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 20px;
            color: #B68B65;
        }
        
        .forgot-form button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(115deg, var(--terracotta) 0%, #C27046 100%);
            color: white;
            border: none;
            border-radius: 60px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 700;
            margin-top: 16px;
        }
        
        .forgot-form button:hover {
            background: linear-gradient(115deg, #C76846 0%, #B55A36 100%);
        }
        
        .message {
            padding: 14px 20px;
            margin: 20px auto;
            max-width: 460px;
            border-radius: 60px;
            text-align: center;
            font-weight: 500;
        }
        
        .error {
            background-color: #FDF2F0;
            color: #B85C3A;
            border-left: 5px solid var(--terracotta);
        }
        
        .success {
            background-color: #ECF7E6;
            color: #5A7A3A;
            border-left: 5px solid var(--olive);
        }
        
        .form-switch {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #7C6857;
        }
        
        .form-switch a {
            color: var(--terracotta);
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-switch a:hover { text-decoration: underline; }
        
        .resend-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .resend-link a {
            color: var(--terracotta);
            text-decoration: none;
        }
        
        footer {
            text-align: center;
            padding: 28px 20px 32px;
            background: #EFE2D4;
            margin-top: 30px;
            color: #896B52;
        }
        
        @media (max-width: 560px) {
            .forgot-form { padding: 28px 22px 36px; }
            .knitting-title { font-size: 1.5rem; }
            .form-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="Dashboard.php"><i class="fas fa-home"></i> DASHBOARD</a> |
            <a href="Contact.php"><i class="fas fa-envelope"></i> CONTACT</a> |
            <a href="About.php"><i class="fas fa-heart"></i> ABOUT</a> |
            <a href="Services.php"><i class="fas fa-scissors"></i> SERVICES</a>
        </nav>
        <p class="welcome-header"><i class="fas fa-yarn"></i> Welcome To JosLee Crocs <i class="fas fa-crochet"></i></p>
    </header>

    <div class="content-wrapper">
        <div class="knitting-card">
            <h1 class="knitting-title">✨ Reset your password ✨</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 'request'): ?>
            <div class="auth-container">
                <div class="forgot-form">
                    <h2 class="form-title">
                        <i class="fas fa-key"></i> Forgot Password? <i class="fas fa-lock"></i>
                    </h2>
                    <p class="subtitle">Enter your email address and we'll send you an OTP.</p>
                    
                    <form method="POST" action="">
                        <input type="email" name="email" placeholder="📧 Email Address" required>
                        <button type="submit" name="request_otp">
                            <i class="fas fa-paper-plane"></i> Send OTP
                        </button>
                    </form>
                    
                    <div class="form-switch">
                        <i class="fas fa-arrow-left"></i> <a href="Login.php">Back to Login</a>
                    </div>
                </div>
            </div>
            
        <?php elseif ($step == 'verify'): ?>
            <div class="auth-container">
                <div class="forgot-form">
                    <h2 class="form-title">
                        <i class="fas fa-envelope"></i> Verify OTP <i class="fas fa-shield-alt"></i>
                    </h2>
                    <p class="subtitle">Enter the 6-digit OTP sent to your email address.</p>
                    
                    <form method="POST" action="">
                        <input type="text" name="otp_code" class="otp-input" placeholder="000000" 
                               maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                        <button type="submit" name="verify_otp">
                            <i class="fas fa-check-circle"></i> Verify OTP
                        </button>
                    </form>
                    
                    <div class="resend-link">
                        <i class="fas fa-redo-alt"></i> <a href="ForgotPassword.php?step=request">Didn't receive OTP? Request again</a>
                    </div>
                    
                    <div class="form-switch">
                        <i class="fas fa-arrow-left"></i> <a href="Login.php">Back to Login</a>
                    </div>
                </div>
            </div>
            
        <?php elseif ($step == 'reset'): ?>
            <div class="auth-container">
                <div class="forgot-form">
                    <h2 class="form-title">
                        <i class="fas fa-sync-alt"></i> Reset Password <i class="fas fa-lock"></i>
                    </h2>
                    <p class="subtitle">Create a new password for your account.</p>
                    
                    <form method="POST" action="">
                        <div class="password-container">
                            <input type="password" id="new_password" name="new_password" placeholder="🔒 New Password" required>
                            <span class="toggle-password" onclick="togglePassword('new_password')">👁️</span>
                        </div>
                        
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="🔒 Confirm Password" required>
                            <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                        </div>
                        
                        <button type="submit" name="reset_password">
                            <i class="fas fa-save"></i> Reset Password
                        </button>
                    </form>
                    
                    <div class="form-switch">
                        <i class="fas fa-arrow-left"></i> <a href="Login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>© 2026 JosLee Crocs Products Website | Knit with joy</p>
    </footer>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '🙈';
            } else {
                input.type = 'password';
                icon.innerHTML = '👁️';
            }
        }
        
        const otpInput = document.querySelector('input[name="otp_code"]');
        if (otpInput) {
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }
    </script>
</body>
</html>
