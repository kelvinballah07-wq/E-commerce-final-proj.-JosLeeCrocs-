<?php
// Add this at the very top of payment_details.php to debug
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<!-- Form was submitted! -->";
}
?>
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

require_once 'Connection.php';

$user_id = $_SESSION['user_id'];

// Check for bulk payment
$is_bulk_payment = false;
$bulk_booking_ids = array();
$bulk_total = 0;
$bulk_products = array();
$selectedProducts = array();
$totalAmount = 0;
$booking_id = 0;
$is_single_payment = false;
$bulk_booking_numbers = array();
$bulk_booking_count = 0;

// FIRST: Check if this is a bulk payment from POST (most important)
if (isset($_POST['is_bulk_payment']) && $_POST['is_bulk_payment'] == 1) {
    $is_bulk_payment = true;
    $bulk_booking_ids = json_decode($_POST['bulk_booking_ids'], true);
    $bulk_total = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
    $bulk_products = json_decode($_POST['products_data'], true);
    $selectedProducts = $bulk_products;
    $totalAmount = $bulk_total;
    $bulk_booking_count = count($bulk_booking_ids);
    
    // Get booking numbers for display
    $bulk_booking_numbers = array();
    foreach ($bulk_booking_ids as $bid) {
        $num_sql = "SELECT booking_number FROM bookings WHERE id = ? AND user_id = ?";
        $num_stmt = $conn->prepare($num_sql);
        $num_stmt->bind_param("ii", $bid, $user_id);
        $num_stmt->execute();
        $num_result = $num_stmt->get_result();
        if ($num_data = $num_result->fetch_assoc()) {
            $bulk_booking_numbers[] = $num_data['booking_number'];
        }
        $num_stmt->close();
    }
    
    // Store in session
    $_SESSION['bulk_payment_data'] = array(
        'booking_ids' => $bulk_booking_ids,
        'total_amount' => $bulk_total,
        'products' => $bulk_products,
        'booking_numbers' => $bulk_booking_numbers,
        'booking_count' => $bulk_booking_count
    );
} 
// SECOND: Check if bulk data exists in session
elseif (isset($_SESSION['bulk_payment_data']) && !empty($_SESSION['bulk_payment_data'])) {
    $bulk_data = $_SESSION['bulk_payment_data'];
    $bulk_booking_ids = $bulk_data['booking_ids'];
    $bulk_total = $bulk_data['total_amount'];
    $bulk_products = isset($bulk_data['products']) ? $bulk_data['products'] : array();
    $selectedProducts = $bulk_products;
    $totalAmount = $bulk_total;
    $is_bulk_payment = true;
    $bulk_booking_numbers = isset($bulk_data['booking_numbers']) ? $bulk_data['booking_numbers'] : array();
    $bulk_booking_count = isset($bulk_data['booking_count']) ? $bulk_data['booking_count'] : count($bulk_booking_ids);
    
    // If booking numbers are missing, fetch them
    if (empty($bulk_booking_numbers) && !empty($bulk_booking_ids)) {
        foreach ($bulk_booking_ids as $bid) {
            $num_sql = "SELECT booking_number FROM bookings WHERE id = ? AND user_id = ?";
            $num_stmt = $conn->prepare($num_sql);
            $num_stmt->bind_param("ii", $bid, $user_id);
            $num_stmt->execute();
            $num_result = $num_stmt->get_result();
            if ($num_data = $num_result->fetch_assoc()) {
                $bulk_booking_numbers[] = $num_data['booking_number'];
            }
            $num_stmt->close();
        }
        $_SESSION['bulk_payment_data']['booking_numbers'] = $bulk_booking_numbers;
    }
}

// THIRD: Handle single payment (only if not bulk)
if (!$is_bulk_payment && isset($_POST['single_payment']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Get booking details
    $booking_sql = "SELECT id, booking_number, total_amount FROM bookings WHERE id = ? AND user_id = ? AND payment_status = 'unpaid'";
    $booking_stmt = $conn->prepare($booking_sql);
    $booking_stmt->bind_param("ii", $booking_id, $user_id);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    
    if ($booking_data = $booking_result->fetch_assoc()) {
        // Get products for this booking
        $items_stmt = $conn->prepare("SELECT product_id, product_name, quantity, subtotal FROM booking_items WHERE booking_id = ?");
        $items_stmt->bind_param("i", $booking_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $products = array();
        while ($item = $items_result->fetch_assoc()) {
            $products[] = $item;
        }
        $items_stmt->close();
        
        // Store in session
        $_SESSION['single_payment_data'] = array(
            'booking_id' => $booking_id,
            'booking_number' => $booking_data['booking_number'],
            'total_amount' => $booking_data['total_amount'],
            'products' => $products
        );
        
        $is_single_payment = true;
        $selectedProducts = $products;
        $totalAmount = $booking_data['total_amount'];
    }
    $booking_stmt->close();
} elseif (!$is_bulk_payment && isset($_SESSION['single_payment_data'])) {
    $single_data = $_SESSION['single_payment_data'];
    $is_single_payment = true;
    $selectedProducts = $single_data['products'];
    $totalAmount = $single_data['total_amount'];
    $booking_id = $single_data['booking_id'];
}

// Get booking ID from various sources (for single payment from product page)
if (!$is_bulk_payment && !$is_single_payment) {
    if (isset($_POST['booking_id'])) {
        $booking_id = intval($_POST['booking_id']);
        $_SESSION['current_booking_id'] = $booking_id;
    } elseif (isset($_SESSION['current_booking']['booking_id'])) {
        $booking_id = $_SESSION['current_booking']['booking_id'];
        $_SESSION['current_booking_id'] = $booking_id;
    } elseif (isset($_SESSION['current_booking_id'])) {
        $booking_id = $_SESSION['current_booking_id'];
    }
}

// Get products from POST or session (for single payment from product page)
if (!$is_bulk_payment && !$is_single_payment) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['products_data'])) {
        $selectedProducts = json_decode($_POST['products_data'], true);
        $_SESSION['selected_products'] = $selectedProducts;
    } elseif (isset($_SESSION['selected_products'])) {
        $selectedProducts = $_SESSION['selected_products'];
    }
    
    // Calculate total amount for single payment
    if (!empty($selectedProducts)) {
        foreach ($selectedProducts as $product) {
            $price = floatval($product['price']);
            $quantity = intval($product['quantity'] ?? 1);
            $totalAmount += $price * $quantity;
        }
    }
}

// If no products found, redirect back
if (empty($selectedProducts) && !$is_bulk_payment) {
    header("Location: Service.php");
    exit();
}

$_SESSION['total_amount'] = $totalAmount;

$paymentMethod = isset($_GET['method']) ? $_GET['method'] : (isset($_POST['payment_method']) ? $_POST['payment_method'] : '');

// Check for password error from process_payment.php
$passwordError = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'password_required') {
        $passwordError = 'Please enter your password to confirm payment';
    } elseif ($_GET['error'] == 'incorrect_password') {
        $passwordError = 'Incorrect password. Payment cannot be processed.';
    } elseif ($_GET['error'] == 'verification_failed') {
        $passwordError = 'Password verification failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details - JosLee Crocs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #6a11cb;
        }

        h1 {
            color: #6a11cb;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .order-summary {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .order-summary h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .total-amount {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: right;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #ddd;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .payment-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-option:hover, .payment-option.selected {
            border-color: #6a11cb;
            background: #f0f5ff;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #6a11cb;
            text-decoration: none;
        }

        .booking-info {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            text-align: center;
        }

        /* Password verification styles */
        .password-verification {
            background: #f8f9fa;
            border: 2px solid #6a11cb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .password-verification h3 {
            color: #6a11cb;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .password-verification p {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .password-input-group {
            margin-bottom: 15px;
        }

        .password-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .password-input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .password-error {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
            display: <?php echo !empty($passwordError) ? 'block' : 'none'; ?>;
        }

        .payment-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .payment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .payment-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .bulk-badge {
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .booking-list {
            background: #e8f4fd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        
        .booking-list-item {
            padding: 5px 0;
            border-bottom: 1px solid #d0e3f0;
        }
        
        .booking-list-item:last-child {
            border-bottom: none;
        }
        
        .single-payment-badge {
            background: #e67e22;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .bulk-summary {
            background: #f0f8ff;
            border-left: 4px solid #27ae60;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .bulk-summary strong {
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Payment Details</h1>
            <p>Complete your payment securely</p>
        </header>

        <?php if ($is_bulk_payment): ?>
            <!-- BULK PAYMENT DISPLAY -->
            <div style="text-align: center;">
                <div class="bulk-badge">📦 Bulk Payment (<?php echo $bulk_booking_count; ?> Bookings)</div>
            </div>
            
            <div class="bulk-summary">
                <strong>📋 Bookings being paid:</strong>
                <?php if (!empty($bulk_booking_numbers)): ?>
                    <?php foreach ($bulk_booking_numbers as $booking_num): ?>
                        <div class="booking-list-item" style="margin-top: 5px;">
                            📄 Booking #: <?php echo htmlspecialchars($booking_num); ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($bulk_booking_ids as $bid): ?>
                        <div class="booking-list-item" style="margin-top: 5px;">
                            📄 Booking ID: #<?php echo htmlspecialchars($bid); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #d0e3f0; font-weight: bold;">
                    Total Bookings: <?php echo $bulk_booking_count; ?>
                </div>
            </div>
        <?php elseif ($is_single_payment && isset($_SESSION['single_payment_data'])): ?>
            <?php $single_data = $_SESSION['single_payment_data']; ?>
            <div style="text-align: center;">
                <div class="single-payment-badge">💰 Single Payment</div>
            </div>
            <div class="booking-info">
                <strong>Booking #:</strong> <?php echo htmlspecialchars($single_data['booking_number']); ?>
            </div>
        <?php elseif ($booking_id > 0): ?>
            <div class="booking-info">
                📄 Booking #: <?php echo $booking_id; ?>
            </div>
        <?php endif; ?>

        <div class="order-summary">
            <h3>Order Summary</h3>
            <?php if ($is_bulk_payment && !empty($bulk_products)): ?>
                <?php 
                // Display products from bulk payment
                $bulk_items_display = array();
                foreach ($bulk_products as $product) {
                    $key = $product['product_name'] ?? $product['name'];
                    if (isset($bulk_items_display[$key])) {
                        $bulk_items_display[$key]['quantity'] += intval($product['quantity'] ?? 1);
                        $bulk_items_display[$key]['total'] += floatval($product['price'] ?? $product['subtotal'] / ($product['quantity'] ?? 1)) * intval($product['quantity'] ?? 1);
                    } else {
                        $bulk_items_display[$key] = array(
                            'name' => $key,
                            'quantity' => intval($product['quantity'] ?? 1),
                            'total' => floatval($product['price'] ?? $product['subtotal'] / ($product['quantity'] ?? 1)) * intval($product['quantity'] ?? 1)
                        );
                    }
                }
                foreach ($bulk_items_display as $item): 
                ?>
                    <div class="product-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span><?php echo number_format($item['total'], 0); ?> Rwf</span>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!empty($selectedProducts)): ?>
                <?php foreach ($selectedProducts as $product): ?>
                    <div class="product-item">
                        <span><?php echo htmlspecialchars($product['product_name'] ?? $product['name']); ?> x <?php echo $product['quantity'] ?? 1; ?></span>
                        <span><?php echo number_format(floatval($product['price'] ?? $product['subtotal'] / ($product['quantity'] ?? 1)) * intval($product['quantity'] ?? 1), 0); ?> Rwf</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="total-amount">
                Total: <?php echo number_format($totalAmount, 0); ?> Rwf
            </div>
        </div>

        <!-- Updated form with password verification -->
        <form id="paymentForm" method="POST" action="process_payment.php">
            
            <input type="hidden" name="total_amount" value="<?php echo $totalAmount; ?>">
            
            <?php if ($is_bulk_payment): ?>
                <!-- Bulk Payment Hidden Fields -->
                <input type="hidden" name="is_bulk_payment" value="1">
                <input type="hidden" name="bulk_booking_ids" value='<?php echo json_encode($bulk_booking_ids); ?>'>
                <input type="hidden" name="products_data" value='<?php echo htmlspecialchars(json_encode($bulk_products), ENT_QUOTES); ?>'>
                <input type="hidden" name="booking_id" value="0">
            <?php elseif ($is_single_payment && isset($_SESSION['single_payment_data'])): ?>
                <!-- Single Payment (from MyProducts) Hidden Fields -->
                <input type="hidden" name="is_single_payment" value="1">
                <input type="hidden" name="existing_booking_id" value="<?php echo $_SESSION['single_payment_data']['booking_id']; ?>">
                <input type="hidden" name="products_data" value='<?php echo htmlspecialchars(json_encode($selectedProducts), ENT_QUOTES); ?>'>
                <input type="hidden" name="booking_id" value="0">
            <?php else: ?>
                <!-- Regular Single Payment Hidden Fields -->
                <input type="hidden" name="products_data" value='<?php echo htmlspecialchars(json_encode($selectedProducts), ENT_QUOTES); ?>'>
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="is_bulk_payment" value="0">
            <?php endif; ?>
            
            <input type="hidden" name="payment_method" id="paymentMethod" value="">

            <div class="payment-options">
                <div class="payment-option" onclick="selectPaymentMethod('mastercard')">💳 MasterCard</div>
                <div class="payment-option" onclick="selectPaymentMethod('visa')">💳 Visa Card</div>
                <div class="payment-option" onclick="selectPaymentMethod('emoney')">📱 Mobile Money</div>
            </div>

            <div id="cardFields" style="display: none;">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" name="card_number" id="cardNumber" placeholder="1234 5678 9012 3456">
                </div>
                <div class="form-group">
                    <label>Expiry Date (MM/YY)</label>
                    <input type="text" name="expiry" id="expiry" placeholder="MM/YY">
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" name="cvv" id="cvv" placeholder="123">
                </div>
            </div>

            <div id="mobileFields" style="display: none;">
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="tel" name="mobile_number" id="mobileNumber" placeholder="0788 123 456">
                </div>
                <div class="form-group">
                    <label>Mobile Money Provider</label>
                    <select name="provider" id="provider">
                        <option value="">Select Provider</option>
                        <option value="MTN">MTN Mobile Money</option>
                        <option value="Airtel">Airtel Money</option>
                        <option value="Tigo">Tigo Cash</option>
                    </select>
                </div>
            </div>

            <!-- Password Verification Section -->
            <div class="password-verification">
                <h3>🔐 Verify Your Identity</h3>
                <p>For your security, please enter your account password to complete this payment.</p>
                
                <div class="password-input-group">
                    <label for="password">Account Password:</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password to confirm payment">
                    <div class="password-error" id="passwordError">
                        <?php echo htmlspecialchars($passwordError); ?>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="payment-btn" id="submitPayment">Complete Payment</button>
        </form>

        <div class="back-link">
            <a href="Service.php">← Back to Services</a>
        </div>
    </div>

    <script>
        let selectedMethod = '';

        function selectPaymentMethod(method) {
            selectedMethod = method;
            document.getElementById('paymentMethod').value = method;
            
            const clickedElement = event.currentTarget;
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            clickedElement.classList.add('selected');
            
            const cardFields = document.getElementById('cardFields');
            const mobileFields = document.getElementById('mobileFields');
            
            if (method === 'mastercard' || method === 'visa') {
                cardFields.style.display = 'block';
                mobileFields.style.display = 'none';
                // Remove required attributes from mobile fields
                document.getElementById('mobileNumber').removeAttribute('required');
                document.getElementById('provider').removeAttribute('required');
                // Add required attributes to card fields
                document.getElementById('cardNumber').setAttribute('required', 'required');
                document.getElementById('expiry').setAttribute('required', 'required');
                document.getElementById('cvv').setAttribute('required', 'required');
            } else if (method === 'emoney') {
                cardFields.style.display = 'none';
                mobileFields.style.display = 'block';
                // Remove required attributes from card fields
                document.getElementById('cardNumber').removeAttribute('required');
                document.getElementById('expiry').removeAttribute('required');
                document.getElementById('cvv').removeAttribute('required');
                // Add required attributes to mobile fields
                document.getElementById('mobileNumber').setAttribute('required', 'required');
                document.getElementById('provider').setAttribute('required', 'required');
            }
        }

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            if (!selectedMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }
            
            const password = document.getElementById('password').value;
            const passwordError = document.getElementById('passwordError');
            
            if (!password) {
                e.preventDefault();
                passwordError.textContent = 'Please enter your password to complete payment';
                passwordError.style.display = 'block';
                return false;
            }
            
            // Validate card fields if card payment selected
            if (selectedMethod === 'mastercard' || selectedMethod === 'visa') {
                const cardNumber = document.getElementById('cardNumber').value;
                const expiry = document.getElementById('expiry').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || !expiry || !cvv) {
                    e.preventDefault();
                    alert('Please fill in all card details');
                    return false;
                }
            }
            
            // Validate mobile fields if mobile payment selected
            if (selectedMethod === 'emoney') {
                const mobileNumber = document.getElementById('mobileNumber').value;
                const provider = document.getElementById('provider').value;
                
                if (!mobileNumber || !provider) {
                    e.preventDefault();
                    alert('Please fill in all mobile money details');
                    return false;
                }
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitPayment');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing Payment...';
        });
        
        // Clear error when user starts typing
        document.getElementById('password').addEventListener('input', function() {
            const passwordError = document.getElementById('passwordError');
            passwordError.style.display = 'none';
            passwordError.textContent = '';
        });
    </script>
</body>
</html>
