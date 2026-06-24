<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

// If user is admin, redirect to admin dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$username = $_SESSION['username'];
$isAdmin = false; // This will always be false for this page since admins are redirected
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JosLee Crocs | My Knitting Studio</title>
    <link rel="stylesheet" href="http://josleecrocs.rf.gd/Style.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ===== ROOT VARIABLES - WARM CROCHET THEME ===== */
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
            --yarn-teal: #6FA3A8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, var(--cream) 0%, #FDF5E6 100%);
            font-family: 'Segoe UI', 'Poppins', 'Nunito', system-ui, -apple-system, sans-serif;
            color: var(--charcoal);
            line-height: 1.5;
            min-height: 100vh;
        }
        
        /* ===== HEADER & NAVIGATION - UPDATED WITH FOOTER COLORS ===== */
        header {
            background: #EFE2D4;  /* Same as footer background */
            padding: 1.5rem 2rem 1rem 2rem;
            border-bottom: 3px solid #E7D2BC;  /* Same as footer border */
            box-shadow: 0 6px 18px rgba(0,0,0,0.04);
            position: relative;
        }
        
        /* ---- Logo area (top left) ---- */
        .logo-area {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 0.5rem;
        }
        .logo-area img {
            height: 65px;
            width: auto;
            display: block;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
            object-fit: contain;
        }
        .logo-area img:hover {
            transform: scale(1.03);
        }
        .logo-text {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #AC6D49;
            letter-spacing: -0.2px;
            text-shadow: 1px 1px 0 #FDF1E0;
            line-height: 1.2;
        }
        .logo-text small {
            font-size: 0.8rem;
            font-weight: 400;
            color: #896B52;  /* Updated to match footer text color */
            display: block;
            margin-top: -2px;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        /* Navigation row */
        .nav-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1.2rem 2rem;
            margin-top: 6px;
        }
        
        nav {
            display: flex;
            gap: 1.8rem;
            flex-wrap: wrap;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        nav a {
            color: #896B52;  /* Updated to match footer text color */
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            padding-bottom: 4px;
            border-bottom: 2px solid transparent;
        }
        
        nav a:hover {
            color: #BB7A4B;  /* Updated hover color */
            border-bottom-color: #E7D2BC;
            transform: translateY(-1px);
        }
        
        .welcome-header {
            font-size: 1.35rem;
            font-weight: 400;
            color: #896B52;  /* Updated to match footer text color */
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
        
        /* ===== USER MENU (WARM PILL) ===== */
        .user-menu {
            position: absolute;
            top: 22px;
            right: 30px;
            background: rgba(255, 250, 240, 0.96);
            backdrop-filter: blur(4px);
            padding: 8px 20px;
            border-radius: 60px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            border: 1px solid #E7D2BC;  /* Updated to match footer border */
            display: flex;
            align-items: center;
            gap: 18px;
            z-index: 20;
        }
        
        .user-menu span {
            color: #6B4C3B;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .user-menu strong {
            color: var(--terracotta);
            font-weight: 800;
            background: #FFF2E4;
            padding: 4px 12px;
            border-radius: 40px;
            margin-left: 4px;
        }
        
        .logout-btn {
            background: var(--honey);
            color: #4F3422;
            border: none;
            padding: 7px 20px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            font-family: inherit;
        }
        
        .logout-btn:hover {
            background: #D99E4C;
            color: white;
            transform: scale(0.96);
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
        
        /* welcome section - handmade style */
        .welcome-section {
            background: linear-gradient(105deg, #FFF7EE 0%, #FFFBF3 100%);
            border-radius: 42px;
            padding: 2rem 2rem;
            margin: 20px 0 30px 0;
            text-align: center;
            box-shadow: 0 6px 14px rgba(130, 75, 35, 0.06);
            border: 1px solid #FDE5C7;
        }
        
        .user-welcome {
            font-size: 2.7rem;
            font-weight: 750;
            background: linear-gradient(125deg, #C2703E, #E8A05E);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .welcome-section p {
            font-size: 1.1rem;
            color: #80604C;
            border-top: 2px dotted #F0DDBB;
            display: inline-block;
            padding-top: 12px;
            margin-top: 6px;
        }
        
        /* ===== DASHBOARD GRID (4 CARDS - CROCHET BUSINESS) ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 28px;
            margin: 45px 0 35px;
        }
        
        .dashboard-card {
            background: var(--soft-white);
            border-radius: 36px;
            padding: 32px 22px 34px;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            box-shadow: 0 15px 30px -12px rgba(0, 0, 0, 0.08);
            text-align: center;
            border: 1px solid #F7E2CA;
            backdrop-filter: blur(2px);
            cursor: pointer;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 40px -16px rgba(105, 65, 30, 0.2);
            border-color: #EECB9E;
            background: #FFFEF9;
        }
        
        .card-icon {
            font-size: 3.2rem;
            margin-bottom: 1rem;
            background: #FEF2E4;
            width: 90px;
            height: 90px;
            line-height: 90px;
            border-radius: 50%;
            margin-left: auto;
            margin-right: auto;
            color: #CA7A4A;
            transition: 0.2s;
        }
        
        .dashboard-card:hover .card-icon {
            background: #FFE7D4;
            transform: scale(1.02);
        }
        
        .dashboard-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #A5663E;
            margin: 18px 0 12px;
            border-bottom: 3px solid #F6DFC3;
            display: inline-block;
            padding-bottom: 8px;
        }
        
        .dashboard-card p {
            color: #866651;
            margin: 16px 0 24px;
            font-size: 0.95rem;
            line-height: 1.45;
            font-weight: 500;
        }
        
        /* buttons consistent with crochet brand */
        button, .dashboard-card button {
            background: #F5DBC1;
            border: none;
            padding: 10px 30px;
            font-weight: 700;
            border-radius: 60px;
            font-size: 0.85rem;
            letter-spacing: 0.4px;
            color: #734C2F;
            cursor: pointer;
            transition: 0.2s;
            font-family: inherit;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        button:hover, .dashboard-card button:hover {
            background: #E7C29F;
            color: #3F2A1C;
            transform: scale(0.97);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .upload-btn {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
            margin: 10px 0;
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
        }
        
        .admin-only {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 5px;
            vertical-align: middle;
        }
        
        /* footer cozy - KEEPING ORIGINAL FOOTER STYLES */
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
        
        /* Responsive touches */
        @media (max-width: 720px) {
            .user-menu {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 15px;
                justify-content: center;
                width: fit-content;
                margin-left: auto;
            }
            header {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
            .logo-area {
                flex-wrap: wrap;
            }
            .logo-area img {
                height: 45px;
            }
            .logo-text {
                font-size: 1.2rem;
            }
            .logo-text small {
                font-size: 0.7rem;
            }
            .nav-row {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
            nav {
                justify-content: center;
                gap: 0.8rem;
            }
            .knitting-title {
                font-size: 1.7rem;
            }
            .user-welcome {
                font-size: 1.9rem;
            }
            .dashboard-grid {
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <!-- ===== LOGO (top left) ===== -->
        <div class="logo-area">
            <img src="uploads/Logo.png" 
                 alt="JosLee Crocs Logo" 
                 title="JosLee Crocs - Handmade with Love">
            <div class="logo-text">
                JosLee Crocs
                <small>handmade with love</small>
            </div>
        </div>

        <!-- Navigation row (below logo) -->
        <div class="nav-row">
            <nav>
                <a href="Dashboard.php"><i class="fas fa-home"></i> DASHBOARD</a>
                <a href="Services.php"><i class="fas fa-scissors"></i> SERVICES</a>
                <a href="About.php"><i class="fas fa-heart"></i> ABOUT</a>
                <a href="Contact.php"><i class="fas fa-envelope"></i> CONTACT</a>
            </nav>
        </div>
        
        <div class="user-menu">
            <span><i class="fas fa-user-circle"></i> Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></span>
            <button class="logout-btn" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </header>

    <div class="content-wrapper">
        <!-- decorative crochet card -->
        <div class="knitting-card">
            <div class="stitch-pattern"></div>
            <div class="yarn-ball"></div>
            <h1 class="knitting-title">✨ Your Knitting Studio ✨</h1>
        </div>
        
        <main>
            <div class="welcome-section">
                <h2 class="user-welcome">
                    <i class="fas fa-hands-clapping"></i> Hello, <?php echo htmlspecialchars($username); ?>! 🎉
                </h2>
                <p>🧶 Welcome back to your JosLee Crocs knitting workspace 🧵</p>
            </div>
            
            <!-- DASHBOARD GRID - 4 CARDS FOR CROCHET BUSINESS (fully functional) -->
            <div class="dashboard-grid">
                <!-- My Cart card -->
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-shopping-basket"></i></div>
                    <h3>My Cart</h3>
                    <p>View and manage your knitting & crochet products</p>
                    <button onclick="location.href='MyProducts.php'">View Cart <i class="fas fa-arrow-right"></i></button>
                </div>
                
                <!-- Pattern Library card -->
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-book-open"></i></div>
                    <h3>Pattern Library</h3>
                    <p>Browse our collection of knitting patterns and amigurumi</p>
                    <button onclick="window.open('https://www.google.com/search?q=crochet+designs', '_blank')">Browse Patterns <i class="fas fa-search"></i></button>
                </div>
                
                <!-- Yarn Shop card -->
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-store"></i></div>
                    <h3>Yarn Shop</h3>
                    <p>Shop for premium yarns and knitting supplies</p>
                    <button onclick="location.href='Service.php'">Go Shopping <i class="fas fa-shopping-cart"></i></button>
                </div>
                
                <!-- Community card -->
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <h3>Community</h3>
                    <p>Connect with other knitting enthusiasts worldwide</p>
                    <button onclick="location.href='Join_Community.php'">Join Community <i class="fas fa-heart"></i></button>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p class="knitting-subtitle"><i class="fas fa-feather-alt"></i> Fueled by happy thoughts and yarn! <i class="fas fa-feather-alt"></i></p>
        <p>© 2026 JosLee Crocs Products Website | Dashboard | Handmade with love</p>
    </footer>

    <script>
        // Preserved logout confirmation (original functionality)
        function confirmLogout() {
            if (confirm('🧶 Are you sure you want to logout from your knitting workspace?')) {
                window.location.href = 'clear_session.php';
            }
        }
        
        // make cards clickable as well (nice UX) without breaking button events
        document.querySelectorAll('.dashboard-card').forEach(card => {
            const btn = card.querySelector('button');
            if (btn) {
                // prevent double trigger when clicking button
                card.addEventListener('click', (e) => {
                    if (e.target !== btn && !btn.contains(e.target)) {
                        btn.click();
                    }
                });
            }
        });
    </script>
</body>
</html>
