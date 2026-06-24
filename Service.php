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

// Handle Add to Cart
if(isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $quantity = (int)$_POST['quantity'];
    
    // Check if product already in cart
    $found = false;
    foreach($_SESSION['cart'] as &$item) {
        if($item['id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        $_SESSION['cart'][] = [
            'id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => $quantity
        ];
    }
    
    // Redirect back to same page to refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Add Selected Products to Bookings
if(isset($_POST['add_selected_to_bookings'])) {
    $selected_products = json_decode($_POST['selected_products_json'], true);
    
    if(!empty($selected_products)) {
        // Generate unique booking number
        $booking_number = 'JLC-' . strtoupper(uniqid());
        $total_amount = 0;
        
        // Calculate total amount
        foreach($selected_products as $product) {
            $total_amount += $product['price'] * $product['quantity'];
        }
        
        // Insert into bookings table - using correct column names
        $insert_booking = "INSERT INTO bookings (booking_number, user_id, total_amount, booking_status, payment_status, booking_date) 
                           VALUES (?, ?, ?, 'pending', 'unpaid', NOW())";
        $stmt = $conn->prepare($insert_booking);
        $stmt->bind_param("sid", $booking_number, $user_id, $total_amount);
        
        if($stmt->execute()) {
            $booking_id = $stmt->insert_id;
            
            // Insert each product into booking_items table - FIXED: removed 'price' column
            $insert_item = "INSERT INTO booking_items (booking_id, product_id, product_name, quantity, subtotal) 
                            VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $conn->prepare($insert_item);
            
            foreach($selected_products as $product) {
                $subtotal = $product['price'] * $product['quantity'];
                $item_stmt->bind_param("iisid", $booking_id, $product['id'], $product['name'], $product['quantity'], $subtotal);
                $item_stmt->execute();
            }
            
            $_SESSION['booking_success'] = "Booking created successfully! Booking #: " . $booking_number;
            header("Location: MyProducts.php");
            exit();
        } else {
            $_SESSION['booking_error'] = "Error creating booking: " . $conn->error;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $_SESSION['booking_error'] = "No products selected.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Add Selected Products to Cart (keep original)
if(isset($_POST['add_selected_to_cart'])) {
    $selected_products = json_decode($_POST['selected_products_json'], true);
    
    if(!empty($selected_products)) {
        foreach($selected_products as $selected) {
            $product_id = $selected['id'];
            $product_name = $selected['name'];
            $product_price = $selected['price'];
            $quantity = $selected['quantity'];
            
            // Check if product already in cart
            $found = false;
            foreach($_SESSION['cart'] as &$item) {
                if($item['id'] == $product_id) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            if(!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product_id,
                    'name' => $product_name,
                    'price' => $product_price,
                    'quantity' => $quantity
                ];
            }
        }
        
        $_SESSION['cart_message'] = "Products added to cart successfully!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch ALL products from database
$sql = "SELECT id, product_name, product_description, product_type, price, image_url, status, quantity, date_ordered, created_at 
        FROM products 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get unpaid bookings count from database instead of session cart
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - JosLee Crocs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #FEF7E8 0%, #FDF2E3 100%);
            min-height: 100vh;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* Decorative knitting card design */
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
            font-size: 2rem;
            font-weight: 700;
            color: #AC6D49;
            letter-spacing: -0.3px;
            text-shadow: 2px 2px 0 #FDF1E0;
            margin: 0;
        }
        
        .tagline {
            color: #7C6857;
            font-size: 1rem;
            margin-top: 10px;
        }

        .cart-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #D97A5C;
            color: white;
            padding: 10px 15px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .cart-badge:hover {
            background: #C27046;
            transform: scale(1.05);
        }

        .upload-btn {
            display: inline-block;
            background: #7A8B5E;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .upload-btn:hover {
            background: #6B7C4E;
            transform: translateY(-2px);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .product-card {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .product-card.out-of-stock {
            opacity: 0.7;
            background: #f5f5f5;
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #7A8B5E;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .out-of-stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #8B3A3A;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #e0e0e0;
        }

        .product-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }

        .product-type {
            display: inline-block;
            background: #e0e0e0;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 5px 0;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin: 10px 0;
            line-height: 1.4;
            flex-grow: 1;
        }

        .product-price {
            color: #D97A5C;
            font-weight: bold;
            font-size: 1.3rem;
            margin: 10px 0;
        }

        .product-quantity {
            color: #888;
            font-size: 0.85rem;
        }

        .product-quantity.out-of-stock {
            color: #8B3A3A;
            font-weight: bold;
        }

        .product-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 10px 0;
        }

        .status-pending_payment {
            background: #FFF3E0;
            color: #E67E22;
        }

        .status-purchased {
            background: #D4EDDA;
            color: #155724;
        }

        .status-cancelled {
            background: #F8D7DA;
            color: #721C24;
        }

        .status-shipped {
            background: #D1ECF1;
            color: #0C5460;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
        }

        .checkbox-container input {
            margin-right: 8px;
            transform: scale(1.3);
        }

        .checkbox-container input:disabled {
            cursor: not-allowed;
        }

        .checkbox-container input:disabled + label {
            color: #999;
            cursor: not-allowed;
        }

        .checkbox-container label {
            font-weight: 500;
            color: #555;
            cursor: pointer;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .quantity-input button {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
        }

        .quantity-input button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
            background: #f0f0f0;
        }

        .quantity-input input {
            width: 60px;
            text-align: center;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .buttons-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
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
            background: #D97A5C;
            color: white;
        }

        .btn-primary:hover {
            background: #C27046;
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(217, 122, 92, 0.4);
        }

        .btn-secondary {
            background: #EFE2D4;
            color: #3E3A35;
        }

        .btn-secondary:hover {
            background: #E5D5C4;
            transform: translateY(-3px);
        }

        .btn-add-to-cart {
            background: #E8B86B;
            color: white;
        }

        .btn-add-to-cart:hover {
            background: #D4A45A;
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(232, 184, 107, 0.4);
        }

        .btn-view-cart {
            background: #7A8B5E;
            color: white;
        }

        .error-message {
            color: #8B3A3A;
            text-align: center;
            margin-top: 20px;
            font-weight: 500;
            display: none;
        }

        .success-message {
            background: #D4EDDA;
            color: #155724;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #A09283;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #EFE2D4;
            color: #896B52;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
            
            .buttons-container {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <a href="MyProducts.php" class="cart-badge">
        🛒 Cart (<?php echo $unpaid_count; ?>)
    </a>

    <div class="container">
        <!-- Decorative Crochet Card Design -->
        <div class="knitting-card">
            <div class="stitch-pattern"></div>
            <div class="yarn-ball"></div>
            <h1 class="knitting-title">✨ Our Crochet Products ✨</h1>
            <p class="tagline">Handcrafted with love and attention to detail</p>
        </div>

        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="success-message" style="display: block;">
                <?php 
                    echo $_SESSION['cart_message'];
                    unset($_SESSION['cart_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['booking_success'])): ?>
            <div class="success-message" style="display: block;">
                <?php 
                    echo $_SESSION['booking_success'];
                    unset($_SESSION['booking_success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['booking_error'])): ?>
            <div class="error-message" style="display: block;">
                <?php 
                    echo $_SESSION['booking_error'];
                    unset($_SESSION['booking_error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <div style="text-align: center;">
                <a href="upload_product.php" class="upload-btn">➕ Upload New Product</a>
            </div>
        <?php endif; ?>

        <div class="products-grid">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <h3>✨ No products available</h3>
                    <p>Check back soon for amazing crochet products!</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): 
                    $isOutOfStock = ($product['quantity'] <= 0) || ($product['status'] === 'purchased');
                    $availableQuantity = $product['quantity'] ?? 1;
                ?>
                    <div class="product-card <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>" 
                         data-product-id="<?php echo $product['id']; ?>"
                         onclick="window.location.href='Product_Details.php?id=<?php echo $product['id']; ?>'">
                        <?php if (!$isOutOfStock && $product['status'] === 'pending_payment'): ?>
                            <div class="product-badge">Available</div>
                        <?php elseif ($isOutOfStock): ?>
                            <div class="out-of-stock-badge">Out of Stock</div>
                        <?php endif; ?>
                        
                        <img class="product-image" 
                             src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://placehold.co/400x300?text=No+Image'); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <span class="product-type"><?php echo htmlspecialchars($product['product_type'] ?? 'Knitted Item'); ?></span>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['product_description'] ?? 'No description available', 0, 100)) . '...'; ?></p>
                        <p class="product-price"><?php echo number_format($product['price'], 0); ?> Rwf</p>
                        <p class="product-quantity <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                            📦 Available: <?php echo $availableQuantity; ?> in stock
                        </p>
                        <span class="product-status status-<?php echo $product['status']; ?>">
                            <?php 
                            if ($isOutOfStock) {
                                echo '❌ Out of Stock';
                            } else {
                                echo '⏳ Available';
                            }
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="error-message" id="errorMessage">
            Please select at least one product to add to cart.
        </div>

        <div class="buttons-container">
            <a href="Dashboard.php" class="btn btn-secondary">
                Back To Dashboard
            </a>
        </div>

        <footer>
            <p>&copy; 2026 JosLee Crocs. All rights reserved.</p>
        </footer>
    </div>

    <form method="POST" id="cartForm" style="display: none;">
        <input type="hidden" name="selected_products_json" id="selectedProductsJson">
        <input type="hidden" name="add_selected_to_cart" value="1">
    </form>

    <!-- New form for bookings -->
    <form method="POST" id="bookingsForm" style="display: none;">
        <input type="hidden" name="selected_products_json" id="bookingsSelectedProductsJson">
        <input type="hidden" name="add_selected_to_bookings" value="1">
    </form>

    <script>
        // Store selected products
        let selectedProducts = [];
        
        function toggleQuantityInput(productId, isChecked) {
            const quantityDiv = document.getElementById(`quantity_${productId}`);
            if (isChecked) {
                quantityDiv.style.display = 'flex';
                // Check if already in selected products
                const existingIndex = selectedProducts.findIndex(p => p.id === productId);
                if (existingIndex === -1) {
                    const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
                    const productName = productCard.querySelector('.product-title').innerText;
                    const productPrice = parseFloat(productCard.querySelector('.product-price').innerText.replace(/[^0-9]/g, ''));
                    const maxQuantity = parseInt(document.getElementById(`qty_${productId}`).max);
                    
                    selectedProducts.push({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: 1,
                        maxQuantity: maxQuantity
                    });
                }
            } else {
                quantityDiv.style.display = 'none';
                // Remove from selected products
                selectedProducts = selectedProducts.filter(p => p.id !== productId);
                // Reset quantity input
                document.getElementById(`qty_${productId}`).value = 1;
            }
            updateSelectedProductsList();
        }
        
        function changeQuantity(productId, delta) {
            const quantityInput = document.getElementById(`qty_${productId}`);
            let newValue = parseInt(quantityInput.value) + delta;
            const max = parseInt(quantityInput.max);
            if (newValue < 1) newValue = 1;
            if (newValue > max) newValue = max;
            quantityInput.value = newValue;
            
            // Update quantity in selected products array
            const productIndex = selectedProducts.findIndex(p => p.id === productId);
            if (productIndex !== -1) {
                selectedProducts[productIndex].quantity = newValue;
            }
            updateSelectedProductsList();
        }
        
        function updateSelectedProductsList() {
            console.log('Selected products:', selectedProducts);
            // Update both hidden inputs
            document.getElementById('selectedProductsJson').value = JSON.stringify(selectedProducts);
            document.getElementById('bookingsSelectedProductsJson').value = JSON.stringify(selectedProducts);
        }
        
        // Add selected products to bookings
        function addSelectedToBookings() {
            // Filter only selected products (checked checkboxes)
            const checkedProducts = [];
            document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
                const productId = parseInt(checkbox.dataset.productId);
                const productData = selectedProducts.find(p => p.id === productId);
                if (productData) {
                    checkedProducts.push(productData);
                }
            });
            
            if (checkedProducts.length === 0) {
                document.getElementById('errorMessage').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('errorMessage').style.display = 'none';
                }, 3000);
                return false;
            }
            
            // Validate quantities
            const hasInvalid = checkedProducts.some(product => product.quantity > product.maxQuantity);
            if (hasInvalid) {
                alert('One or more products have quantity exceeding available stock.');
                return false;
            }
            
            // Set the JSON data
            document.getElementById('bookingsSelectedProductsJson').value = JSON.stringify(checkedProducts);
            
            // Submit the bookings form
            document.getElementById('bookingsForm').submit();
        }
        
        // Keep original add to cart function
        function addSelectedToCart() {
            // Filter only selected products (checked checkboxes)
            const checkedProducts = [];
            document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
                const productId = parseInt(checkbox.dataset.productId);
                const productData = selectedProducts.find(p => p.id === productId);
                if (productData) {
                    checkedProducts.push(productData);
                }
            });
            
            if (checkedProducts.length === 0) {
                document.getElementById('errorMessage').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('errorMessage').style.display = 'none';
                }, 3000);
                return false;
            }
            
            // Validate quantities
            const hasInvalid = checkedProducts.some(product => product.quantity > product.maxQuantity);
            if (hasInvalid) {
                alert('One or more products have quantity exceeding available stock.');
                return false;
            }
            
            // Set the JSON data
            document.getElementById('selectedProductsJson').value = JSON.stringify(checkedProducts);
            
            // Submit the form
            document.getElementById('cartForm').submit();
        }
        
        function proceedToPayment() {
            // Get checked products for payment
            const checkedProducts = [];
            document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
                const productId = parseInt(checkbox.dataset.productId);
                const productData = selectedProducts.find(p => p.id === productId);
                if (productData) {
                    checkedProducts.push(productData);
                }
            });
            
            if (checkedProducts.length === 0) {
                document.getElementById('errorMessage').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('errorMessage').style.display = 'none';
                }, 3000);
                return false;
            }
            
            // Calculate total amount
            let totalAmount = 0;
            checkedProducts.forEach(product => {
                totalAmount += product.price * product.quantity;
            });
            
            const proceedBtn = document.getElementById('proceedButton');
            proceedBtn.disabled = true;
            proceedBtn.textContent = 'Processing...';
            
            fetch('process_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'products_data': JSON.stringify(checkedProducts),
                    'total_amount': totalAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('selectedProducts', JSON.stringify(checkedProducts));
                    sessionStorage.setItem('booking_number', data.booking_number);
                    sessionStorage.setItem('booking_id', data.booking_id);
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'payment_details.php';
                    
                    const productsInput = document.createElement('input');
                    productsInput.type = 'hidden';
                    productsInput.name = 'products_data';
                    productsInput.value = JSON.stringify(checkedProducts);
                    form.appendChild(productsInput);
                    
                    const bookingInput = document.createElement('input');
                    bookingInput.type = 'hidden';
                    bookingInput.name = 'booking_id';
                    bookingInput.value = data.booking_id;
                    form.appendChild(bookingInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    alert('Error creating booking: ' + data.message);
                    proceedBtn.disabled = false;
                    proceedBtn.textContent = 'Proceed to Payment';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create booking. Please try again.');
                proceedBtn.disabled = false;
                proceedBtn.textContent = 'Proceed to Payment';
            });
        }
        
        // Hide error message when user selects products
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                document.getElementById('errorMessage').style.display = 'none';
            });
        });
    </script>
</body>
</html>
