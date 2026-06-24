<?php
// Force session to use cookies
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);

// Set session cookie parameters
session_set_cookie_params(0, '/', '', false, true);

// Set timezone to UTC
date_default_timezone_set('UTC');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
    header("Location: Dashboard.php");
    exit();
}

// Determine which step to display
$step = $_GET['step'] ?? ($_SESSION['signup_step'] ?? 'form');

// Get signup data from session
$signup_email = $_SESSION['signup_email'] ?? '';
$signup_username = $_SESSION['signup_username'] ?? '';

// If the page is opened with ?step=verify but the session is missing,
// return the user to the registration form.
if ($step === 'verify' && empty($signup_email)) {
    $step = 'form';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Create Account - JosLee Crocs | Knitting Studio</title>
    <link rel="stylesheet" href="http://josleecrocs.rf.gd/Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --cream: #FEF7E8;
            --warm-sand: #F5E6D3;
            --terracotta: #D97A5C;
            --deep-burgundy: #8B3A3A;
            --olive: #7A8B5E;
            --honey: #E8B86B;
            --charcoal: #3E3A35;
            --soft-white: #FFFFFF;
            --yarn-pink: #E8A598;
            --sage: #A8B88B;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--cream) 0%, #FDF2E3 100%);
            font-family: 'Segoe UI', 'Poppins', 'Nunito', system-ui, -apple-system, sans-serif;
            color: var(--charcoal);
            line-height: 1.5;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(112deg, #FFFFFF 0%, #FEF4E8 100%);
            padding: 1.5rem 2rem 1rem 2rem;
            border-bottom: 3px solid var(--honey);
            box-shadow: 0 6px 18px rgba(0,0,0,0.04);
            position: relative;
        }

        nav {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 0.75rem;
        }

        nav a {
            color: var(--terracotta);
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            padding-bottom: 4px;
            border-bottom: 2px solid transparent;
        }

        nav a:hover {
            color: var(--deep-burgundy);
            border-bottom-color: var(--honey);
            transform: translateY(-1px);
        }

        .welcome-header {
            font-size: 1.35rem;
            font-weight: 400;
            color: #7A5A4B;
            margin-top: 0.5rem;
            letter-spacing: -0.2px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(232, 184, 107, 0.12);
            padding: 6px 18px;
            border-radius: 50px;
        }

        .welcome-header i {
            color: var(--honey);
            font-size: 1.2rem;
        }

        .content-wrapper {
            max-width: 1300px;
            margin: 1.8rem auto;
            padding: 0 28px 50px 28px;
        }

        .knitting-card {
            position: relative;
            background: linear-gradient(115deg, #FFFBF4 0%, #FEF9EF 100%);
            border-radius: 56px 56px 48px 48px;
            padding: 28px 20px;
            margin-bottom: 32px;
            text-align: center;
            overflow: hidden;
            box-shadow: 0 15px 30px -10px rgba(85, 55, 35, 0.12);
            border: 1px solid #FFE4BE;
        }

        .stitch-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: repeating-linear-gradient(90deg, #E7C294, #E7C294 18px, #F5DEB3 18px, #F5DEB3 36px);
        }

        .yarn-ball {
            position: absolute;
            bottom: 8px;
            right: 15px;
            width: 85px;
            height: 85px;
            background: radial-gradient(circle at 35% 35%, #EAB07E, #BC6F44);
            border-radius: 50%;
            opacity: 0.2;
            filter: blur(4px);
            pointer-events: none;
        }

        .knitting-title {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 2.4rem;
            font-weight: 700;
            color: #AC6D49;
            letter-spacing: -0.3px;
            text-shadow: 2px 2px 0 #FDF1E0;
            margin: 0;
        }

        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0 40px;
        }

        .login-form {
            max-width: 460px;
            width: 100%;
            margin: 0 auto;
            padding: 36px 32px 42px;
            background: var(--soft-white);
            border-radius: 48px;
            box-shadow: 0 25px 45px -12px rgba(85, 55, 35, 0.25);
            border: 1px solid #F7E2CA;
            transition: transform 0.2s;
        }

        .login-form:hover {
            transform: translateY(-3px);
        }

        .form-title {
            text-align: center;
            margin-bottom: 28px;
            color: #8B3E3E;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .form-title i {
            color: var(--honey);
            font-size: 2rem;
        }

        .login-form input {
            width: 100%;
            padding: 14px 16px;
            margin: 10px 0;
            border: 2px solid #F0E0CE;
            border-radius: 60px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #FFFDF9;
            font-family: inherit;
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--honey);
            box-shadow: 0 0 0 3px rgba(232, 184, 107, 0.2);
            background: white;
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
            width: 100%;
            padding: 14px 50px 14px 16px;
            margin: 10px 0;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 20px;
            user-select: none;
            color: #B68B65;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--terracotta);
        }

        .login-form button {
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
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(217, 122, 92, 0.3);
            font-family: inherit;
        }

        .login-form button:hover {
            background: linear-gradient(115deg, #C76846 0%, #B55A36 100%);
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(217, 122, 92, 0.4);
        }

        .login-form button:disabled {
            background: linear-gradient(115deg, #C0A08A 0%, #B18F76 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
            transition: color 0.2s;
            border-bottom: 1px dotted transparent;
        }

        .form-switch a:hover {
            color: var(--deep-burgundy);
            border-bottom-color: var(--honey);
        }

        .error-message {
            background-color: #FDF2F0;
            color: #B85C3A;
            border-left: 5px solid var(--terracotta);
            padding: 14px 20px;
            margin: 20px auto;
            max-width: 460px;
            border-radius: 60px;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .success-message {
            background-color: #ECF7E6;
            color: #5A7A3A;
            border-left: 5px solid var(--olive);
            padding: 14px 20px;
            margin: 20px auto;
            max-width: 460px;
            border-radius: 60px;
            text-align: center;
            font-weight: 500;
        }

        .info-message {
            background-color: #E8F4FD;
            color: #0C5460;
            border-left: 5px solid #17A2B8;
            padding: 14px 20px;
            margin: 20px auto;
            max-width: 460px;
            border-radius: 60px;
            text-align: center;
            font-weight: 500;
        }

        .loading-spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .subtitle {
            text-align: center;
            color: #7C6857;
            margin-bottom: 20px;
        }

        .resend-link {
            text-align: center;
            margin-top: 15px;
        }

        .resend-link a {
            color: var(--terracotta);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .resend-link a:hover {
            color: var(--deep-burgundy);
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 28px 20px 32px;
            background: #EFE2D4;
            margin-top: 30px;
            border-top: 2px solid #E7D2BC;
            color: #896B52;
        }

        .knitting-subtitle {
            font-size: 1rem;
            font-style: italic;
            font-family: 'Georgia', serif;
            margin-bottom: 10px;
            color: #BB7A4B;
        }

        @media (max-width: 560px) {
            .login-form {
                padding: 28px 22px 36px;
            }
            .knitting-title {
                font-size: 1.7rem;
            }
            .form-title {
                font-size: 1.6rem;
            }
            .content-wrapper {
                padding: 0 16px 40px 16px;
            }
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
            <div class="stitch-pattern"></div>
            <div class="yarn-ball"></div>
            <h1 class="knitting-title">✨ Join our knitting family! ✨</h1>
        </div>

        <div id="messageContainer"></div>

        <main>
            <div class="auth-container">
                <div class="login-form">
                    <?php if ($step == 'form'): ?>
                        <h2 class="form-title">
                            <i class="fas fa-user-plus"></i>
                            Create Account
                            <i class="fas fa-feather-alt"></i>
                        </h2>
                        <form id="createAccountForm">
                            <input type="text" id="username" name="username" placeholder="👤 Username" required>
                            <input type="email" id="email" name="email" placeholder="📧 Email Address" required>
                            <div class="password-container">
                                <input type="password" id="password" name="password" placeholder="🔒 Password" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility()">👁️</span>
                            </div>
                            <div class="password-container">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="🔒 Confirm Password" required>
                                <span class="toggle-password" onclick="toggleConfirmPasswordVisibility()">👁️</span>
                            </div>
                            <button type="submit" id="createAccountBtn">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                            <div class="form-switch">
                                <i class="fas fa-sign-in-alt"></i> Already have an account?
                                <a href="Login.php">Login Here</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <h2 class="form-title">
                            <i class="fas fa-envelope"></i>
                            Verify Your Email
                            <i class="fas fa-shield-alt"></i>
                        </h2>
                        <p class="subtitle">
                            Please enter the 6-digit OTP sent to <strong><?php echo htmlspecialchars($signup_email); ?></strong>
                        </p>
                        <form id="verifyOtpForm">
                            <input type="hidden" id="email_hidden" name="email_hidden" value="<?php echo htmlspecialchars($signup_email); ?>">
                            <input type="text" id="otp_code" name="otp_code" class="otp-input" placeholder="000000"
                                   maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                            <button type="submit" id="verifyOtpBtn">
                                <i class="fas fa-check-circle"></i> Verify & Create Account
                            </button>
                        </form>
                        <div class="resend-link">
                            <i class="fas fa-redo-alt"></i>
                            <a href="#" onclick="resendOTP(); return false;">Didn't receive OTP? Resend</a>
                        </div>
                        <div class="form-switch" style="margin-top: 20px;">
                            <i class="fas fa-arrow-left"></i>
                            <a href="#" onclick="goBackToForm(); return false;">Back to registration</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <footer>
        <p class="knitting-subtitle"><i class="fas fa-feather-alt"></i> Fueled by happy thoughts. <i class="fas fa-feather-alt"></i></p>
        <p>© 2026 JosLee Crocs Products Website | Knit with joy</p>
    </footer>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelectorAll('.toggle-password')[0];

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = '👁️';
            }
        }

        function toggleConfirmPasswordVisibility() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const toggleIcon = document.querySelectorAll('.toggle-password')[1];

            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                toggleIcon.innerHTML = '🙈';
            } else {
                confirmPasswordInput.type = 'password';
                toggleIcon.innerHTML = '👁️';
            }
        }

        function goBackToForm() {
            fetch('clear_signup_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear'
            }).then(() => {
                window.location.href = 'CreateAccount.php';
            }).catch(() => {
                window.location.href = 'CreateAccount.php';
            });
        }

        function resendOTP() {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = '';

            const infoDiv = document.createElement('div');
            infoDiv.className = 'info-message';
            infoDiv.innerHTML = '🔄 Resending OTP to your email...';
            messageContainer.appendChild(infoDiv);

            const email = '<?php echo htmlspecialchars($signup_email); ?>';

            fetch('send_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(result => {
                messageContainer.innerHTML = '';
                if (result.success) {
                    const successDiv = document.createElement('div');
                    successDiv.className = 'success-message';
                    successDiv.innerHTML = '✅ ' + result.message;
                    messageContainer.appendChild(successDiv);
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = '❌ ' + result.message;
                    messageContainer.appendChild(errorDiv);
                }
            })
            .catch(error => {
                messageContainer.innerHTML = '';
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '❌ Failed to resend OTP. Please try again.';
                messageContainer.appendChild(errorDiv);
            });
        }

        /* ===========================================================
           CREATE ACCOUNT FORM
           FIX: use "?." so this safely does nothing on the verify step,
           instead of throwing an error that would block the code below
           (which attaches the verify form's submit handler).
           =========================================================== */
        document.getElementById('createAccountForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const createAccountBtn = document.getElementById('createAccountBtn');
            const originalButtonText = createAccountBtn.innerHTML;
            const messageContainer = document.getElementById('messageContainer');

            if (password !== confirmPassword) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '❌ Passwords do not match. Please try again.';
                messageContainer.innerHTML = '';
                messageContainer.appendChild(errorDiv);
                return;
            }

            if (password.length < 6) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '❌ Password must be at least 6 characters long.';
                messageContainer.innerHTML = '';
                messageContainer.appendChild(errorDiv);
                return;
            }

            createAccountBtn.disabled = true;
            createAccountBtn.innerHTML = '<span class="loading-spinner"></span> Sending OTP...';
            messageContainer.innerHTML = '';

            try {
                const formData = new URLSearchParams();
                formData.append('username', username);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('action', 'send_otp');

                const response = await fetch('signup_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'info-message';
                    infoDiv.innerHTML = '📧 ' + result.message;
                    messageContainer.appendChild(infoDiv);

                    setTimeout(() => {
                        window.location.href = 'CreateAccount.php?step=verify';
                    }, 1500);
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = '❌ ' + result.message;
                    messageContainer.appendChild(errorDiv);

                    createAccountBtn.disabled = false;
                    createAccountBtn.innerHTML = originalButtonText;
                }
            } catch (error) {
                console.error('Error:', error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '❌ Connection error. Please try again.';
                messageContainer.appendChild(errorDiv);

                createAccountBtn.disabled = false;
                createAccountBtn.innerHTML = originalButtonText;
            }
        });

        /* ===========================================================
           VERIFICATION FORM
           This now reliably attaches because the block above no
           longer throws when createAccountForm is absent.
           =========================================================== */
        document.getElementById('verifyOtpForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const otpCode = document.getElementById('otp_code').value;
            const email = document.getElementById('email_hidden').value;
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            const originalButtonText = verifyOtpBtn.innerHTML;
            const messageContainer = document.getElementById('messageContainer');

            verifyOtpBtn.disabled = true;
            verifyOtpBtn.innerHTML = '<span class="loading-spinner"></span> Verifying...';
            messageContainer.innerHTML = '';

            try {
                const formData = new URLSearchParams();
                formData.append('otp_code', otpCode);
                formData.append('email', email);

                const response = await fetch('verify_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const successDiv = document.createElement('div');
                    successDiv.className = 'success-message';
                    successDiv.innerHTML = '✅ ' + result.message + ' Redirecting to login...';
                    messageContainer.appendChild(successDiv);

                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = '❌ ' + result.message;
                    messageContainer.appendChild(errorDiv);

                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.innerHTML = originalButtonText;
                    document.getElementById('otp_code').value = '';
                    document.getElementById('otp_code').focus();
                }
            } catch (error) {
                console.error('Error:', error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '❌ Connection error. Please try again.';
                messageContainer.appendChild(errorDiv);

                verifyOtpBtn.disabled = false;
                verifyOtpBtn.innerHTML = originalButtonText;
            }
        });

        // Allow only numbers in OTP field
        const otpInput = document.querySelector('#otp_code');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }
    </script>
</body>
</html>
