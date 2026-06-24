<?php
session_start();
require_once 'Connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$products_data_raw = isset($_POST['products_data']) ? $_POST['products_data'] : '';
$total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;

if (empty($products_data_raw)) {
    echo json_encode(['success' => false, 'message' => 'No products selected']);
    exit();
}

$products_data = json_decode($products_data_raw, true);
if (empty($products_data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid products data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Generate unique booking number
$booking_number = 'BK' . date('Ymd') . '_' . uniqid() . '_' . rand(1000, 9999);

// Start transaction
$conn->begin_transaction();

try {
    // Insert into bookings table
    $booking_stmt = $conn->prepare("INSERT INTO bookings (booking_number, user_id, user_name, user_email, total_amount, booking_status, payment_status, booking_date) VALUES (?, ?, ?, ?, ?, 'pending', 'unpaid', NOW())");
    
    if (!$booking_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $booking_stmt->bind_param("sisss", $booking_number, $user_id, $user_name, $user_email, $total_amount);
    
    if (!$booking_stmt->execute()) {
        throw new Exception("Execute failed: " . $booking_stmt->error);
    }
    
    $booking_id = $conn->insert_id;
    
    // Insert booking items
    $item_stmt = $conn->prepare("INSERT INTO booking_items (booking_id, product_id, product_name, product_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$item_stmt) {
        throw new Exception("Item prepare failed: " . $conn->error);
    }
    
    foreach ($products_data as $product) {
        $product_id = intval($product['id']);
        $product_name = $product['name'];
        $product_price = floatval($product['price']);
        $quantity = intval($product['quantity']);
        $subtotal = $product_price * $quantity;
        
        // Fixed: 6 variables need 6 type characters (i=integer, s=string, d=double/decimal)
        // i = integer, s = string, d = double/decimal
        $item_stmt->bind_param("iisddd", $booking_id, $product_id, $product_name, $product_price, $quantity, $subtotal);
        
        if (!$item_stmt->execute()) {
            throw new Exception("Item execute failed for product: $product_name - " . $item_stmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Store booking info in session
    $_SESSION['current_booking'] = [
        'booking_id' => $booking_id,
        'booking_number' => $booking_number,
        'total_amount' => $total_amount,
        'products' => $products_data
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Booking created successfully',
        'booking_number' => $booking_number,
        'booking_id' => $booking_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
