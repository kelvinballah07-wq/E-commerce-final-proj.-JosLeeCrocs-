<?php
session_start();
require_once 'Connection.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// Initialize cart if not exists
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($product_id <= 0) {
    header("Location: Service.php");
    exit();
}

// Fetch product details from database
$sql = "SELECT id, product_name, product_description, product_type, price, image_url, status, quantity, date_ordered, created_at 
        FROM products 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    header("Location: Service.php");
    exit();
}

$product = $result->fetch_assoc();

// Handle Add to Cart - NOW CREATES BOOKING (same as Service.php Add Selected to Cart)
if(isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $quantity = (int)$_POST['quantity'];
    
    // Create products array for booking
    $products_data = [[
        'id' => $product_id,
        'name' => $product_name,
        'price' => $product_price,
        'quantity' => $quantity,
        'maxQuantity' => $product['quantity']
    ]];
    
    $total_amount = $product_price * $quantity;
    
    // Generate unique booking number
    $booking_number = 'JLC-' . strtoupper(uniqid());
    
    // Insert into bookings table
    $insert_booking = "INSERT INTO bookings (booking_number, user_id, total_amount, booking_status, payment_status, booking_date) 
                       VALUES (?, ?, ?, 'pending', 'unpaid', NOW())";
    $stmt = $conn->prepare($insert_booking);
    $stmt->bind_param("sid", $booking_number, $user_id, $total_amount);
    
    if($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // Insert product into booking_items table
        $insert_item = "INSERT INTO booking_items (booking_id, product_id, product_name, quantity, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($insert_item);
        $subtotal = $product_price * $quantity;
        $item_stmt->bind_param("iisid", $booking_id, $product_id, $product_name, $quantity, $subtotal);
        $item_stmt->execute();
        
        $_SESSION['booking_success'] = "Product added to cart! Booking #: " . $booking_number;
        header("Location: Product_Details.php?id=" . $product_id);
        exit();
    } else {
        $_SESSION['booking_error'] = "Error creating booking: " . $conn->error;
        header("Location: Product_Details.php?id=" . $product_id);
        exit();
    }
}

// Handle Buy Now (creates booking and redirects to payment - same as Service.php)
if(isset($_POST['buy_now'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $quantity = (int)$_POST['quantity'];
    
    // Create products array for booking
    $products_data = [[
        'id' => $product_id,
        'name' => $product_name,
        'price' => $product_price,
        'quantity' => $quantity,
        'maxQuantity' => $product['quantity']
    ]];
    
    $total_amount = $product_price * $quantity;
    
    // Generate unique booking number
    $booking_number = 'JLC-' . strtoupper(uniqid());
    
    // Insert into bookings table
    $insert_booking = "INSERT INTO bookings (booking_number, user_id, total_amount, booking_status, payment_status, booking_date) 
                       VALUES (?, ?, ?, 'pending', 'unpaid', NOW())";
    $stmt = $conn->prepare($insert_booking);
    $stmt->bind_param("sid", $booking_number, $user_id, $total_amount);
    
    if($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // Insert product into booking_items table
        $insert_item = "INSERT INTO booking_items (booking_id, product_id, product_name, quantity, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($insert_item);
        $subtotal = $product_price * $quantity;
        $item_stmt->bind_param("iisid", $booking_id, $product_id, $product_name, $quantity, $subtotal);
        $item_stmt->execute();
        
        // Store booking info in session and redirect to payment
        $_SESSION['booking_success'] = "Booking created successfully! Booking #: " . $booking_number;
        
        // Redirect to payment page with booking data
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Redirecting to Payment...</title>
        </head>
        <body>
            <form method="POST" action="payment_details.php" id="paymentForm">
                <input type="hidden" name="products_data" value='<?php echo json_encode($products_data); ?>'>
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="booking_number" value="<?php echo $booking_number; ?>">
                <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
            </form>
            <script>document.getElementById('paymentForm').submit();</script>
        </body>
        </html>
        <?php
        exit();
    } else {
        $_SESSION['booking_error'] = "Error creating booking: " . $conn->error;
        header("Location: Product_Details.php?id=" . $product_id);
        exit();
    }
}

// Get unpaid bookings count
$unpaid_count = 0;
if($isLoggedIn) {
    $unpaid_sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND payment_status = 'unpaid' AND booking_status != 'cancelled'";
    $unpaid_stmt = $conn->prepare($unpaid_sql);
    $unpaid_stmt->bind_param("i", $user_id);
    $unpaid_stmt->execute();
    $unpaid_result = $unpaid_stmt->get_result();
    if($unpaid_row = $unpaid_result->fetch_assoc()) {
        $unpaid_count = $unpaid_row['count'];
    }
}

$isOutOfStock = ($product['quantity'] <= 0) || ($product['status'] === 'purchased');
$availableQuantity = $product['quantity'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - JosLee Crocs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Poppins', system-ui, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #FEF7E8 0%, #FDF2E3 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Cart Badge */
        .cart-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #D97A5C 0%, #C27046 100%);
            color: white;
            padding: 12px 18px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(217, 122, 92, 0.3);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .cart-badge:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #C76846 0%, #B55A36 100%);
        }

        /* Navigation */
        .nav-bar {
            background: linear-gradient(112deg, #FFFFFF 0%, #FEF4E8 100%);
            padding: 1rem 2rem;
            border-bottom: 3px solid #E8B86B;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #D97A5C;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .nav-links a:hover {
            color: #8B3A3A;
            border-bottom: 2px solid #E8B86B;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(232, 184, 107, 0.12);
            padding: 6px 18px;
            border-radius: 50px;
        }

        .logout-btn {
            background: #E8B86B;
            color: #4F3422;
            border: none;
            padding: 6px 16px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #D99E4C;
            color: white;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 10px 20px;
            border-radius: 40px;
            text-decoration: none;
            color: #A5663E;
            font-weight: 600;
            margin-bottom: 25px;
            transition: all 0.2s;
            border: 1px solid #F7E2CA;
        }

        .back-button:hover {
            background: #FEF2E4;
            transform: translateX(-5px);
        }

        /* Product Details Card */
        .product-details-card {
            background: white;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 20px 40px -12px rgba(85, 55, 35, 0.2);
            border: 1px solid #FFE4BE;
        }

        .product-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        /* Image Section */
        .product-image-section {
            background: linear-gradient(135deg, #FEF9EF 0%, #FFFBF4 100%);
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
        }

        .product-main-image {
            width: 100%;
            max-width: 400px;
            border-radius: 24px;
            object-fit: cover;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Info Section */
        .product-info-section {
            padding: 40px;
        }

        .product-badge {
            display: inline-block;
            background: #E8B86B;
            color: #4F3422;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .out-of-stock-badge {
            background: #dc3545;
            color: white;
        }

        .product-title {
            font-size: 2.2rem;
            color: #A5663E;
            margin-bottom: 10px;
            font-family: 'Playfair Display', Georgia, serif;
        }

        .product-type {
            display: inline-block;
            background: #FEF2E4;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 0.85rem;
            color: #D97A5C;
            margin-bottom: 20px;
        }

        .product-price {
            font-size: 2rem;
            font-weight: bold;
            color: #D97A5C;
            margin: 20px 0;
        }

        .product-description {
            color: #5E4B3A;
            line-height: 1.8;
            margin: 20px 0;
            border-top: 1px solid #F0E0CE;
            border-bottom: 1px solid #F0E0CE;
            padding: 20px 0;
        }

        .product-stock {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            padding: 10px;
            background: #F8F6F2;
            border-radius: 12px;
        }

        .stock-available {
            color: #28a745;
            font-weight: bold;
        }

        .stock-out {
            color: #dc3545;
            font-weight: bold;
        }

        /* Quantity Selector */
        .quantity-section {
            margin: 25px 0;
        }

        .quantity-label {
            font-weight: 600;
            color: #6B4C3B;
            margin-bottom: 10px;
            display: block;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #F0E0CE;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            color: #A5663E;
            transition: all 0.2s;
        }

        .quantity-btn:hover:not(:disabled) {
            background: #FEF2E4;
            border-color: #E8B86B;
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-input {
            width: 80px;
            height: 40px;
            text-align: center;
            font-size: 1.1rem;
            border: 2px solid #F0E0CE;
            border-radius: 12px;
            font-weight: bold;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #D97A5C 0%, #C27046 100%);
            color: white;
            flex: 1;
            justify-content: center;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(217, 122, 92, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: white;
            color: #A5663E;
            border: 2px solid #F0E0CE;
            flex: 1;
            justify-content: center;
        }

        .btn-secondary:hover {
            background: #FEF2E4;
            border-color: #E8B86B;
        }

        .btn-outline {
            background: transparent;
            color: #D97A5C;
            border: 2px solid #D97A5C;
        }

        .btn-outline:hover {
            background: #D97A5C;
            color: white;
        }

        /* Message Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Related Products */
        .related-section {
            margin-top: 50px;
        }

        .related-title {
            font-size: 1.5rem;
            color: #A5663E;
            margin-bottom: 20px;
            font-family: 'Playfair Display', Georgia, serif;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .related-card {
            background: white;
            border-radius: 24px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid #F7E2CA;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .related-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 12px;
        }

        .related-name {
            font-weight: bold;
            color: #A5663E;
        }

        .related-price {
            color: #D97A5C;
            font-weight: bold;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 30px;
            background: #EFE2D4;
            margin-top: 50px;
            border-top: 2px solid #E7D2BC;
            color: #896B52;
        }

        @media (max-width: 768px) {
            .product-details-grid {
                grid-template-columns: 1fr;
            }
            
            .product-image-section {
                min-height: 300px;
                padding: 20px;
            }
            
            .product-info-section {
                padding: 25px;
            }
            
            .product-title {
                font-size: 1.6rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .nav-bar {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <a href="MyProducts.php" class="cart-badge">
        <i class="fas fa-shopping-cart"></i> Cart (<?php echo $unpaid_count; ?>)
    </a>

    <div class="nav-bar">
        <div class="nav-links">
            <a href="Dashboard.php"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="Service.php"><i class="fas fa-store"></i> PRODUCTS</a>
            <a href="About.php"><i class="fas fa-heart"></i> ABOUT</a>
            <a href="Contact.php"><i class="fas fa-envelope"></i> CONTACT</a>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong></span>
            <button class="logout-btn" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </div>

    <div class="container">
        <a href="Service.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>

        <?php if (isset($_SESSION['booking_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['booking_error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['booking_error']; unset($_SESSION['booking_error']); ?>
            </div>
        <?php endif; ?>

        <div class="product-details-card">
            <div class="product-details-grid">
                <!-- Product Image -->
                <div class="product-image-section">
                    <img class="product-main-image" 
                         src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://placehold.co/600x500?text=JosLee+Crocs'); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                </div>
                
                <!-- Product Info -->
                <div class="product-info-section">
                    <?php if ($isOutOfStock): ?>
                        <div class="product-badge out-of-stock-badge">
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </div>
                    <?php else: ?>
                        <div class="product-badge">
                            <i class="fas fa-check-circle"></i> Available
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    <span class="product-type">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['product_type'] ?? 'Knitted Item'); ?>
                    </span>
                    
                    <div class="product-price">
                        <?php echo number_format($product['price'], 0); ?> Rwf
                    </div>
                    
                    <div class="product-description">
                        <i class="fas fa-quote-left" style="color: #E8B86B; margin-right: 8px;"></i>
                        <?php echo nl2br(htmlspecialchars($product['product_description'] ?? 'No description available for this product.')); ?>
                    </div>
                    
                    <div class="product-stock">
                        <i class="fas fa-box"></i>
                        <span>Available Stock:</span>
                        <?php if ($isOutOfStock): ?>
                            <span class="stock-out">Out of Stock</span>
                        <?php else: ?>
                            <span class="stock-available"><?php echo $availableQuantity; ?> units available</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$isOutOfStock): ?>
                        <form method="POST" id="addToCartForm">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                            <input type="hidden" name="quantity" id="finalQuantity" value="1">
                            <input type="hidden" name="add_to_cart" value="1">
                            
                            <div class="quantity-section">
                                <label class="quantity-label"><i class="fas fa-sort-amount-up"></i> Select Quantity:</label>
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                    <input type="number" id="quantityInput" class="quantity-input" value="1" min="1" max="<?php echo $availableQuantity; ?>" readonly>
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="buyNow()">
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="action-buttons">
                            <button class="btn btn-primary" disabled style="opacity:0.6; cursor:not-allowed;">
                                <i class="fas fa-ban"></i> Out of Stock
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Related Products Section -->
        <?php
        // Fetch related products (same type, excluding current product)
        $related_sql = "SELECT id, product_name, price, image_url FROM products 
                        WHERE product_type = ? AND id != ? AND quantity > 0 AND status != 'purchased'
                        LIMIT 4";
        $related_stmt = $conn->prepare($related_sql);
        $related_stmt->bind_param("si", $product['product_type'], $product_id);
        $related_stmt->execute();
        $related_result = $related_stmt->get_result();
        
        if ($related_result->num_rows > 0):
        ?>
        <div class="related-section">
            <h2 class="related-title"><i class="fas fa-heart"></i> You May Also Like</h2>
            <div class="related-grid">
                <?php while($related = $related_result->fetch_assoc()): ?>
                    <a href="Product_Details.php?id=<?php echo $related['id']; ?>" class="related-card">
                        <img class="related-image" 
                             src="<?php echo htmlspecialchars($related['image_url'] ?: 'https://placehold.co/300x200?text=Yarn+Art'); ?>" 
                             alt="<?php echo htmlspecialchars($related['product_name']); ?>">
                        <div class="related-name"><?php echo htmlspecialchars($related['product_name']); ?></div>
                        <div class="related-price"><?php echo number_format($related['price'], 0); ?> Rwf</div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p><i class="fas fa-feather-alt"></i> Fueled by happy thoughts and yarn! <i class="fas fa-feather-alt"></i></p>
        <p>&copy; 2026 JosLee Crocs Products Website | Handmade Crochet Creations</p>
    </footer>

    <script>
        let currentQuantity = 1;
        const maxQuantity = <?php echo $availableQuantity; ?>;
        
        function changeQuantity(delta) {
            let newValue = currentQuantity + delta;
            if (newValue < 1) newValue = 1;
            if (newValue > maxQuantity) newValue = maxQuantity;
            currentQuantity = newValue;
            document.getElementById('quantityInput').value = currentQuantity;
            document.getElementById('finalQuantity').value = currentQuantity;
        }
        
        function buyNow() {
            const quantity = currentQuantity;
            const productId = <?php echo $product['id']; ?>;
            const productName = "<?php echo htmlspecialchars($product['product_name']); ?>";
            const productPrice = <?php echo $product['price']; ?>;
            
            // Create a form to submit buy now
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const productIdInput = document.createElement('input');
            productIdInput.type = 'hidden';
            productIdInput.name = 'product_id';
            productIdInput.value = productId;
            form.appendChild(productIdInput);
            
            const productNameInput = document.createElement('input');
            productNameInput.type = 'hidden';
            productNameInput.name = 'product_name';
            productNameInput.value = productName;
            form.appendChild(productNameInput);
            
            const productPriceInput = document.createElement('input');
            productPriceInput.type = 'hidden';
            productPriceInput.name = 'product_price';
            productPriceInput.value = productPrice;
            form.appendChild(productPriceInput);
            
            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'quantity';
            quantityInput.value = quantity;
            form.appendChild(quantityInput);
            
            const buyNowInput = document.createElement('input');
            buyNowInput.type = 'hidden';
            buyNowInput.name = 'buy_now';
            buyNowInput.value = '1';
            form.appendChild(buyNowInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function confirmLogout() {
            if (confirm('🧶 Are you sure you want to logout?')) {
                window.location.href = 'clear_session.php';
            }
        }
    </script>
</body>
</html>
