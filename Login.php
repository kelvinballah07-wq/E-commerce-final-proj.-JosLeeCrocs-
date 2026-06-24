<?php
// Force session to use cookies
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);

// Set session cookie parameters
session_set_cookie_params(0, '/', '', false, true);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
    header("Location: Dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Login - JosLee Crocs | Knitting Studio</title>
    <link rel="stylesheet" href="http://josleecrocs.rf.gd/Style.css">
    <!-- Font Awesome for crafty icons -->
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
        
        /* ===== HEADER & NAVIGATION ===== */
        header {
            background: linear-gradient(112deg, #FFFFFF 0%, #FEF4E8 100%);
            padding: 1.5rem 2rem 1rem 2rem;
            border-bottom: 3px solid var(--honey);
            box-shadow: 0 6px 18px rgba(0,0,0,0.04);
            position: relative;
        }

        /* ===== LOGO + NAV ROW ===== */
        .header-top {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 0.75rem;
        }

        .site-logo {
            height: 64px;
            width: 64px;
            object-fit: contain;
            border-radius: 50%;
            background: var(--soft-white);
            box-shadow: 0 4px 12px rgba(133, 86, 41, 0.15);
            border: 2px solid var(--honey);
            flex-shrink: 0;
        }
        
        nav {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 0;
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
        
        /* ===== MAIN CONTENT ===== */
        .content-wrapper {
            max-width: 1300px;
            margin: 1.8rem auto;
            padding: 0 28px 50px 28px;
        }
        
        /* knitting card (decorative crochet element) */
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
        
        /* ===== LOGIN FORM - WARM CROCHET THEME ===== */
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
        
        /* Message containers */
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
        
        /* footer cozy */
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
        
        /* Responsive */
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
            .site-logo {
                height: 52px;
                width: 52px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-top">
            <img src="uploads/Logo.png" alt="JosLee Crocs Logo" class="site-logo">
            <nav>
                <a href="Dashboard.php"><i class="fas fa-home"></i> DASHBOARD</a> |
                <a href="Contact.php"><i class="fas fa-envelope"></i> CONTACT</a> |
                <a href="About.php"><i class="fas fa-heart"></i> ABOUT</a> |
                <a href="Services.php"><i class="fas fa-scissors"></i> SERVICES</a>
            </nav>
        </div>
        <p class="welcome-header"><i class="fas fa-yarn"></i> Welcome To JosLee Crocs <i class="fas fa-crochet"></i></p>
    </header>

    <div class="content-wrapper">
        <!-- decorative crochet card -->
        <div class="knitting-card">
            <div class="stitch-pattern"></div>
            <div class="yarn-ball"></div>
            <h1 class="knitting-title">✨ A love knitting! ✨</h1>
        </div>
        
        <div id="messageContainer"></div>
        
        <main>
            <div class="auth-container">
                <div class="login-form">
                    <h2 class="form-title">
                        <i class="fas fa-user-circle"></i> 
                        User Login
                        <i class="fas fa-feather-alt"></i>
                    </h2>
                    <form id="loginForm">
                        <input type="email" id="email" name="email" placeholder="📧 Email Address" required>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="🔒 Password" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility()">👁️</span>
                        </div>
                        <button type="submit" id="loginBtn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                        <div class="form-switch">
                            <i class="fas fa-question-circle"></i> Forgot password? 
                            <a href="ForgotPassword.php">Forgot Password</a>
                        </div>
                        <div class="form-switch">
                            <i class="fas fa-user-plus"></i> Don't have an account? 
                            <a href="CreateAccount.php">Create Account</a>
                        </div>
                    </form>
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
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = '👁️';
            }
        }
        
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const originalButtonText = loginBtn.innerHTML;
            const messageContainer = document.getElementById('messageContainer');
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="loading-spinner"></span> Logging in...';
            messageContainer.innerHTML = '';
            
            try {
                const formData = new URLSearchParams();
                formData.append('email', email);
                formData.append('password', password);
                
                const response = await fetch('login_process.php', {
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
                    successDiv.innerHTML = '✅ ' + result.message + ' 🧶 Redirecting...';
                    messageContainer.appendChild(successDiv);
                    
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = '❌ ' + result.message;
                    messageContainer.appendChild(errorDiv);
                    
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = originalButtonText;
                    document.getElementById('password').value = '';
                    document.getElementById('password').focus();
                }
            } catch (error) {
                console.error('Error:', error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '❌ Connection error. Please check if the server is running. 🧵';
                messageContainer.appendChild(errorDiv);
                
                loginBtn.disabled = false;
                loginBtn.innerHTML = originalButtonText;
            }
        });
    </script>
</body>
</html>
