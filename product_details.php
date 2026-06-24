<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Connection.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header("Location: view_store.php");
    exit();
}

// Fetch product details with user information
$sql = "SELECT p.*, u.username, u.email
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: view_store.php");
    exit();
}

$product = $result->fetch_assoc();

// Check if user is logged in and if they are the owner or admin
$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isOwner = $isLoggedIn && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['user_id'];

// Get related products (same type, exclude current product)
$related_sql = "SELECT id, product_name, price, image_url, status 
                FROM products 
                WHERE product_type = ? AND id != ? 
                LIMIT 4";
$related_stmt = $conn->prepare($related_sql);
$product_type = $product['product_type'];
$related_stmt->bind_param("si", $product_type, $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
$related_products = [];
while ($row = $related_result->fetch_assoc()) {
    $related_products[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - JosLee Crocs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f0e8 0%, #e8dfd3 100%);
            min-height: 100vh;
        }

        /* Navigation Bar */
        .navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #8b7355;
            text-decoration: none;
        }

        .logo span {
            color: #e74c3c;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #e74c3c;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-login, .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-cart {
            background: #27ae60;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        /* Breadcrumb */
        .breadcrumb {
            margin-bottom: 2rem;
            color: #666;
        }

        .breadcrumb a {
            color: #8b7355;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Product Details Main Section */
        .product-details {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .product-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        /* Product Image Gallery */
        .product-gallery {
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 16px;
            background: #f2eee9;
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-badge.available {
            background: #27ae60;
        }

        /* Product Info */
        .product-info h1 {
            font-size: 2rem;
            color: #2d3e2b;
            margin-bottom: 0.5rem;
        }

        .product-type {
            display: inline-block;
            background: #f0f0f0;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 2rem;
            font-weight: bold;
            color: #e74c3c;
            margin: 1rem 0;
        }

        .product-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            padding: 1rem 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin: 1rem 0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .product-description {
            margin: 1.5rem 0;
        }

        .product-description h3 {
            color: #2d3e2b;
            margin-bottom: 0.5rem;
        }

        .product-description p {
            color: #666;
            line-height: 1.6;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 1rem 0;
        }

        .status-pending_payment {
            background: #fff3cd;
            color: #856404;
        }

        .status-purchased {
            background: #d4edda;
            color: #155724;
        }

        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Quantity Selector */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .quantity-selector label {
            font-weight: 600;
            color: #333;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .quantity-btn {
            background: #f0f0f0;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }

        .quantity-btn:hover {
            background: #ddd;
        }

        .quantity-input input {
            width: 60px;
            text-align: center;
            border: none;
            padding: 0.5rem;
            font-size: 1rem;
        }

        .quantity-input input:focus {
            outline: none;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #8b7355;
            color: #8b7355;
        }

        .btn-outline:hover {
            background: #8b7355;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        /* Seller Info */
        .seller-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 16px;
            margin-top: 1.5rem;
        }

        .seller-info h4 {
            color: #2d3e2b;
            margin-bottom: 0.5rem;
        }

        .seller-info p {
            color: #666;
            margin: 0.25rem 0;
        }

        /* Related Products */
        .related-products {
            margin-top: 3rem;
        }

        .related-products h2 {
            color: #2d3e2b;
            margin-bottom: 1.5rem;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .related-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            text-decoration: none;
            transition: transform 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .related-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .related-info {
            padding: 1rem;
        }

        .related-title {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .related-price {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Cart Notification */
        .cart-notification {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #27ae60;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            transform: translateX(400px);
            transition: transform 0.3s;
            z-index: 1000;
        }

        .cart-notification.show {
            transform: translateX(0);
        }

        footer {
            text-align: center;
            margin-top: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 24px;
            color: #666;
        }

        @media (max-width: 768px) {
            .product-content {
                grid-template-columns: 1fr;
            }
            .navbar {
                flex-direction: column;
                text-align: center;
            }
            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="view_store.php" class="logo">🧶 JosLee <span>Crocs</span></a>
        <div class="user-info">
            <?php if ($isLoggedIn): ?>
                <span style="color: #333;">👋 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="Dashboard.php" class="btn-login">📊 Dashboard</a>
            <?php else: ?>
                <a href="Login.php" class="btn-login">🔐 Login</a>
                <a href="CreateAccount.php" class="btn-login" style="background: #27ae60;">📝 Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="view_store.php">Home</a> / 
            <a href="services.php">Services</a> / 
            <span><?php echo htmlspecialchars($product['product_name']); ?></span>
        </div>

        <!-- Product Details -->
        <div class="product-details">
            <div class="product-content">
                <!-- Product Image Gallery -->
                <div class="product-gallery">
                    <img class="main-image" 
                         src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://placehold.co/600x400?text=No+Image'); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <?php if ($product['status'] === 'pending_payment'): ?>
                        <div class="product-badge available">Available</div>
                    <?php elseif ($product['status'] === 'purchased'): ?>
                        <div class="product-badge" style="background: #27ae60;">Sold</div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    <span class="product-type">🧵 <?php echo htmlspecialchars($product['product_type'] ?? 'Knitted Item'); ?></span>
                    
                    <div class="product-price"><?php echo number_format($product['price'], 0); ?> Rwf</div>
                    
                    <div class="product-meta">
                        <div class="meta-item">📅 Ordered: <?php echo date('F d, Y', strtotime($product['date_ordered'])); ?></div>
                        <div class="meta-item">📦 Available: <?php echo $product['quantity'] ?? 1; ?> in stock</div>
                        <div class="meta-item">🆔 Product ID: #<?php echo $product['id']; ?></div>
                    </div>

                    <div class="product-description">
                        <h3>📝 Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['product_description'] ?? 'No description available.')); ?></p>
                    </div>

                    <!-- Seller Information -->
                    <div class="seller-info">
                        <h4>👤 Seller Information</h4>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($product['username']); ?></p>
                        <p><strong>Member since:</strong> <?php echo date('F Y', strtotime($product['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
        <div class="related-products">
            <h2>🔄 You May Also Like</h2>
            <div class="related-grid">
                <?php foreach ($related_products as $related): ?>
                    <a href="product_details.php?id=<?php echo $related['id']; ?>" class="related-card">
                        <img class="related-image" 
                             src="<?php echo htmlspecialchars($related['image_url'] ?? 'https://placehold.co/400x300?text=No+Image'); ?>" 
                             alt="<?php echo htmlspecialchars($related['product_name']); ?>">
                        <div class="related-info">
                            <div class="related-title"><?php echo htmlspecialchars($related['product_name']); ?></div>
                            <div class="related-price"><?php echo number_format($related['price'], 0); ?> Rwf</div>
                            <small style="color: #888;"><?php echo $related['status'] === 'pending_payment' ? 'Available' : 'Sold'; ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <footer>
            <p class="knitting-subtitle">Fueled by happy thoughts and yarn! 🧶</p>
            <p>&copy; 2025 JosLee Crocs Products Store | Handcrafted with love</p>
        </footer>
    </div>
</body>
</html>
