<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Dashboard.php");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get statistics
require_once 'Connection.php';

// Count total products
$result = $conn->query("SELECT COUNT(*) as total FROM products");
$totalProducts = $result->fetch_assoc()['total'];

// Count pending orders
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'pending_payment'");
$pendingOrders = $result->fetch_assoc()['total'];

// Count purchased products
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'purchased'");
$purchasedProducts = $result->fetch_assoc()['total'];

// Count total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $result->fetch_assoc()['total'];

// Get total sales count
$result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE payment_status = 'paid' OR booking_status = 'completed'");
$totalSales = $result->fetch_assoc()['total'];

// Get total revenue
$result = $conn->query("SELECT SUM(total_amount) as revenue FROM bookings WHERE payment_status = 'paid' OR booking_status = 'completed'");
$totalRevenue = $result->fetch_assoc()['revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JosLee Crocs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FEF7E8 0%, #FDF2E3 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 48px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(85, 55, 35, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #E8B86B;
            flex-wrap: wrap;
            gap: 1rem;
        }

        h1 {
            color: #3E3A35;
            font-size: 1.8rem;
        }

        .admin-badge {
            background: #D97A5C;
            color: white;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            margin-left: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #F5E6D3;
            padding: 8px 20px;
            border-radius: 40px;
        }

        .user-menu span {
            color: #7A5A4B;
        }

        .user-menu strong {
            color: #D97A5C;
        }

        .logout-btn {
            background: #8B3A3A;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #6B2A2A;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #D97A5C 0%, #C27046 100%);
            color: white;
            padding: 20px;
            border-radius: 32px;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .stat-label {
            margin-top: 10px;
            opacity: 0.9;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-card {
            background: #FFFDF9;
            padding: 25px;
            text-align: center;
            border-radius: 32px;
            text-decoration: none;
            color: #3E3A35;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #F7E2CA;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(85, 55, 35, 0.15);
            border-color: #E8B86B;
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .action-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #D97A5C;
        }

        .action-desc {
            font-size: 0.85rem;
            color: #7C6857;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .nav-link {
            background: #F5E6D3;
            padding: 8px 15px;
            border-radius: 30px;
            text-decoration: none;
            color: #7A5A4B;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: #E8B86B;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Admin Dashboard <span class="admin-badge">Administrator</span></h1>
                <p style="color: #7C6857; margin-top: 5px;">Welcome back, <strong style="color: #D97A5C;"><?php echo htmlspecialchars($username); ?></strong>!</p>
            </div>
            <div class="user-menu">
                <span>👤 <strong><?php echo $username; ?></strong></span>
                <button class="logout-btn" onclick="window.location.href='clear_session.php'">🚪 Logout</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalProducts; ?></div>
                <div class="stat-label">📦 Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pendingOrders; ?></div>
                <div class="stat-label">⏳ Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $purchasedProducts; ?></div>
                <div class="stat-label">✅ Purchased Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">👥 Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalSales; ?></div>
                <div class="stat-label">💰 Completed Sales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalRevenue, 0); ?> RWF</div>
                <div class="stat-label">💵 Total Revenue</div>
            </div>
        </div>

        <div class="actions">
            <a href="upload_product.php" class="action-card">
                <div class="action-icon">📦</div>
                <div class="action-title">Upload New Product</div>
                <div class="action-desc">Add new crochet products to the store</div>
            </a>
            <a href="manage_products.php" class="action-card">
                <div class="action-icon">📋</div>
                <div class="action-title">Manage Products</div>
                <div class="action-desc">View and manage all products</div>
            </a>
            <a href="manage_users.php" class="action-card">
                <div class="action-icon">👥</div>
                <div class="action-title">Manage Users</div>
                <div class="action-desc">View and manage user accounts</div>
            </a>
            <a href="view_store.php" class="action-card">
                <div class="action-icon">🛍️</div>
                <div class="action-title">View Store</div>
                <div class="action-desc">Go to the main store page</div>
            </a>
            <a href="sales_record.php" class="action-card">
                <div class="action-icon">💰</div>
                <div class="action-title">Sales Records</div>
                <div class="action-desc">View all completed sales and revenue</div>
            </a>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php?confirm=yes';
            }
        }
    </script>
</body>
</html>
