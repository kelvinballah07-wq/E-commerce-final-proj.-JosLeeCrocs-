<?php
// Start session to check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the same Connection.php file
require_once 'Connection.php';

// Fetch all products from database
$sql = "SELECT id, product_name, product_description, product_type, price, image_url, status, quantity, created_at 
        FROM products 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get logged in user info
$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store - JosLee Crocs</title>
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

        /* Header/Navbar Styles */
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

        .user-name {
            color: #333;
            font-weight: 600;
        }

        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 5px;
        }

        .btn-login, .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .btn-login:hover, .btn-logout:hover {
            background: #c0392b;
        }

        .btn-upload {
            background: #27ae60;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-upload:hover {
            background: #219a52;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #8b7355 0%, #6b5340 100%);
            border-radius: 24px;
            color: white;
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Search and Filter Section */
        .filter-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 40px;
            font-size: 1rem;
        }

        .filter-box select {
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 40px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }

        /* Product Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: #f2eee9;
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #e74c3c;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d3e2b;
            margin-bottom: 0.5rem;
        }

        .product-type {
            display: inline-block;
            background: #f0f0f0;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.75rem;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .product-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-pending_payment {
            background: #fff3cd;
            color: #856404;
        }

        .status-purchased {
            background: #d4edda;
            color: #155724;
        }

        .btn-add-cart {
            width: 100%;
            padding: 0.75rem;
            background: #8b7355;
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-add-cart:hover {
            background: #6b5340;
        }

        .btn-view-details {
            width: 100%;
            padding: 0.75rem;
            background: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 0.5rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-view-details:hover {
            background: #e0e0e0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 24px;
            color: #999;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        /* Footer */
        footer {
            text-align: center;
            margin-top: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 24px;
            color: #666;
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

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .products-grid {
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
                <span class="user-name">👋 <?php echo htmlspecialchars($username); ?></span>
                <?php if ($isAdmin): ?>
                    <span class="admin-badge">Admin</span>
                    <a href="upload_product.php" class="btn-upload">+ Upload</a>
                <?php endif; ?>
                <a href="Dashboard.php" class="btn-login">📊 Dashboard</a>
            <?php else: ?>
                <a href="Login.php" class="btn-login">Login</a>
                <a href="CreateAccount.php" class="btn-upload">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>Welcome to JosLee Crocs Store</h1>
            <p>Discover handcrafted crochet products made with love and passion</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search products..." onkeyup="filterProducts()">
            </div>
            <div class="filter-box">
                <select id="typeFilter" onchange="filterProducts()">
                    <option value="all">All Types</option>
                    <option value="Knitted Item">Knitted Item</option>
                    <option value="Sweater">Sweater</option>
                    <option value="Hat">Hat</option>
                    <option value="Blanket">Blanket</option>
                    <option value="Accessory">Accessory</option>
                    <option value="Home Decor">Home Decor</option>
                    <option value="Crochet">Crochet</option>
                </select>
            </div>
        </div>

        <!-- Products Grid -->
        <div id="productsContainer">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <h3>✨ No products available</h3>
                    <p>Check back soon for amazing crochet products!</p>
                    <?php if ($isAdmin): ?>
                        <p style="margin-top: 1rem;">
                            <a href="upload_product.php" style="color: #e74c3c;">Click here to upload your first product</a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="products-grid" id="productsGrid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>" data-type="<?php echo htmlspecialchars($product['product_type']); ?>">
                            <?php if ($product['status'] === 'pending_payment'): ?>
                                <div class="product-badge">Available</div>
                            <?php endif; ?>
                            <img class="product-image" 
                                 src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://placehold.co/400x300?text=No+Image'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <span class="product-type"><?php echo htmlspecialchars($product['product_type'] ?? 'Knitted Item'); ?></span>
                                <p class="product-description">
                                    <?php echo htmlspecialchars(substr($product['product_description'] ?? 'No description available', 0, 100)) . '...'; ?>
                                </p>
                                <div class="product-price"><?php echo number_format($product['price'], 0); ?> Rwf</div>
                                <div class="product-meta">
                                    <span>📦 Stock: <?php echo $product['quantity'] ?? 1; ?></span>
                                    <span>📅 Added: <?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p class="knitting-subtitle">Fueled by happy thoughts and yarn! 🧶</p>
            <p>&copy; 2025 JosLee Crocs Products Store | Handcrafted with love</p>
        </footer>
    </div>

    <script>
        // Filter products by search and type
        function filterProducts() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const typeValue = document.getElementById('typeFilter').value;
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const productName = product.getAttribute('data-name');
                const productType = product.getAttribute('data-type');
                
                const matchesSearch = productName.includes(searchValue);
                const matchesType = typeValue === 'all' || productType === typeValue;
                
                if (matchesSearch && matchesType) {
                    product.style.display = '';
                } else {
                    product.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
