<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>About - JosLee Crocs | Knitting Studio</title>
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
            --sage: #A8B88B;
            --lavender: #C5B9D0;
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
        
        /* User menu for logged-in users */
        .user-menu {
            position: absolute;
            top: 22px;
            right: 30px;
            background: rgba(255, 250, 240, 0.96);
            backdrop-filter: blur(4px);
            padding: 8px 20px;
            border-radius: 60px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            border: 1px solid #F9E2C1;
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
        
        /* knitting card - decorative crochet element */
        .knitting-card {
            position: relative;
            background: linear-gradient(115deg, #FFFBF4 0%, #FEF9EF 100%);
            border-radius: 56px 56px 48px 48px;
            padding: 42px 48px;
            margin-bottom: 32px;
            overflow: hidden;
            box-shadow: 0 15px 30px -10px rgba(85, 55, 35, 0.12);
            border: 1px solid #FFE4BE;
        }
        
        @media (max-width: 768px) {
            .knitting-card {
                padding: 28px 24px;
            }
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
            width: 100px;
            height: 100px;
            background: radial-gradient(circle at 35% 35%, #EAB07E, #BC6F44);
            border-radius: 50%;
            opacity: 0.12;
            filter: blur(5px);
            pointer-events: none;
        }
        
        /* About content styling */
        .knitting-title {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #AC6D49;
            text-align: center;
            font-size: 2.6rem;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 0 #FDF1E0;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .knitting-title i {
            color: var(--honey);
            font-size: 2.2rem;
        }
        
        .story-content {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .story-text {
            font-family: 'Georgia', 'Times New Roman', serif;
            line-height: 1.8;
            color: #4A3A2E;
            font-size: 1.08rem;
            text-align: justify;
            margin-bottom: 1.8rem;
        }
        
        .highlight-section {
            background: rgba(232, 184, 107, 0.1);
            border-left: 4px solid var(--honey);
            padding: 20px 28px;
            margin: 28px 0;
            border-radius: 20px;
        }
        
        .highlight-section strong {
            color: var(--terracotta);
            font-size: 1.2rem;
            display: block;
            margin-bottom: 12px;
        }
        
        .owners-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            margin: 35px 0 25px;
        }
        
        .owner-card {
            background: white;
            border-radius: 28px;
            padding: 24px 28px;
            text-align: center;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            border: 1px solid #F7E2CA;
            transition: transform 0.2s;
        }
        
        .owner-card:hover {
            transform: translateY(-5px);
        }
        
        .owner-icon {
            font-size: 3rem;
            color: var(--terracotta);
            margin-bottom: 12px;
        }
        
        .owner-card h4 {
            font-size: 1.3rem;
            color: #A5663E;
            margin-bottom: 8px;
        }
        
        .owner-card p {
            color: #7A6857;
            font-size: 0.9rem;
        }
        
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin: 30px 0;
        }
        
        .mv-card {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 24px;
            padding: 24px;
            text-align: center;
            border: 1px solid #F3DFC7;
        }
        
        .mv-card i {
            font-size: 2.2rem;
            color: var(--honey);
            margin-bottom: 12px;
        }
        
        .mv-card h3 {
            color: var(--terracotta);
            margin-bottom: 12px;
            font-size: 1.4rem;
        }
        
        .mv-card p {
            color: #5E4B3A;
            line-height: 1.6;
        }
        
        /* Dashboard button */
        .button-container {
            text-align: center;
            margin-top: 40px;
        }
        
        .dashboard-button {
            background: linear-gradient(115deg, var(--terracotta) 0%, #C27046 100%);
            color: white;
            border: none;
            padding: 14px 36px;
            font-size: 1.1rem;
            border-radius: 60px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 14px rgba(217, 122, 92, 0.3);
            font-weight: 700;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-family: inherit;
        }
        
        .dashboard-button:hover {
            background: linear-gradient(115deg, #C76846 0%, #B55A36 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(217, 122, 92, 0.4);
        }
        
        .dashboard-button:active {
            transform: translateY(0);
        }
        
        /* footer */
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
        
        @media (max-width: 768px) {
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
            .knitting-title {
                font-size: 1.9rem;
            }
            .mission-vision {
                grid-template-columns: 1fr;
            }
            .owners-grid {
                flex-direction: column;
                align-items: center;
            }
        }
        
        .signature {
            text-align: right;
            font-style: italic;
            margin-top: 20px;
            color: #9C7B63;
            font-family: 'Georgia', serif;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="Dashboard.php"><i class="fas fa-home"></i> DASHBOARD</a> |
            <a href="Services.php"><i class="fas fa-scissors"></i> SERVICES</a> |
            <a href="About.php"><i class="fas fa-heart"></i> ABOUT</a> |
            <a href="Contact.php"><i class="fas fa-envelope"></i> CONTACT</a>
        </nav>
        
        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="user-menu">
            <span><i class="fas fa-user-circle"></i> Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Crafter'); ?></strong></span>
            <button class="logout-btn" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php endif; ?>
        
        <p class="welcome-header"><i class="fas fa-yarn"></i> JosLee Crocs | Handcrafted With Heart <i class="fas fa-crochet"></i></p>
    </header>

    <div class="content-wrapper">
        <div class="knitting-card">
            <div class="stitch-pattern"></div>
            <div class="yarn-ball"></div>
            
            <h1 class="knitting-title">
                <i class="fas fa-heart"></i> 
                OUR STORY 
                <i class="fas fa-feather-alt"></i>
            </h1>
            
            <div class="story-content">
                <p class="story-text">
                    <i class="fas fa-quote-left" style="color: var(--honey); margin-right: 8px;"></i>
                    Established in 2016, our crochet business was born from a simple spark of inspiration: the desire to create something beautiful, meaningful, and made entirely by hand. What began as a quiet pastime quickly transformed into a passion-driven venture when friends and family started requesting custom-made pieces — and soon, so did strangers.
                </p>
                
                <p class="story-text">
                    Founded by Lee, and later joined by co-owner Jos, we decided to turn our love for yarn and creativity into a full-fledged business. With just a few hooks, a growing stash of yarn, and a shared vision, we set out to build more than just a brand — we wanted to build a community.
                </p>
                
                <div class="highlight-section">
                    <strong><i class="fas fa-lightbulb"></i> Our Inspiration</strong>
                    <p>Our inspiration comes from the textures of everyday life, the colors of nature, and the timeless tradition of handmade craftsmanship. Every piece we create tells a story — of warmth, care, and creativity — and carries a little piece of our hearts in every stitch.</p>
                </div>
                
                <div class="mission-vision">
                    <div class="mv-card">
                        <i class="fas fa-eye"></i>
                        <h3>Our Vision</h3>
                        <p>To become a leading name in handmade crochet, known not only for high-quality products but also for nurturing a vibrant, inclusive, and supportive crafting community.</p>
                    </div>
                    <div class="mv-card">
                        <i class="fas fa-bullseye"></i>
                        <h3>Our Mission</h3>
                        <p>To create beautifully handcrafted crochet items that bring joy and comfort to our customers, while empowering fellow makers through education, collaboration, and creativity — especially within our growing Crochet Club (CC) community.</p>
                    </div>
                </div>
                
                <div class="owners-grid">
                    <div class="owner-card">
                        <div class="owner-icon"><i class="fas fa-user-circle"></i></div>
                        <h4>Leetra S. Gibson</h4>
                        <p>Founder & Creative Director</p>
                        <p style="font-size: 0.85rem; margin-top: 8px;"><i class="fas fa-palette"></i> The visionary behind every design</p>
                    </div>
                    <div class="owner-card">
                        <div class="owner-icon"><i class="fas fa-user-circle"></i></div>
                        <h4>Josie J. Bealdeh</h4>
                        <p>Co-Owner & Operations Lead</p>
                        <p style="font-size: 0.85rem; margin-top: 8px;"><i class="fas fa-chart-line"></i> Keeping the business running smoothly</p>
                    </div>
                </div>
                
                <p class="story-text">
                    Together, we blend artistry with purpose, striving to turn a ball of yarn into something that makes someone's day a little brighter. Every stitch is made with intention, every product carries a story, and every customer becomes part of our growing family.
                </p>
                
                <div class="signature">
                    <i class="fas fa-feather-alt"></i> With love, threads, and endless creativity <br>
                    — Lee & Jos
                </div>
                
                <div class="button-container">
                    <a href="Dashboard.php" class="dashboard-button">
                        <i class="fas fa-home"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p class="knitting-subtitle"><i class="fas fa-feather-alt"></i> Fueled by happy thoughts and yarn! <i class="fas fa-feather-alt"></i></p>
        <p>© 2026 JosLee Crocs Products Website | Handmade Crochet Creations</p>
    </footer>

    <script>
        function confirmLogout() {
            if (confirm('🧶 Are you sure you want to logout from your knitting workspace?')) {
                window.location.href = 'clear_session.php';
            }
        }
    </script>
</body>
</html>
