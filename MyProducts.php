<?php
// Set timezone to Rwanda/Kigali (East African Time)
date_default_timezone_set('Africa/Kigali');
// Rest of your code...
// Start session and check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

// Use the same Connection.php file
require_once 'Connection.php';

// Get the logged-in user's ID from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle product removal from unpaid booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $booking_item_id = $_POST['booking_item_id'];
    $booking_id = $_POST['booking_id'];
    
    // Verify the booking belongs to the logged-in user and is unpaid
    $check_sql = "SELECT b.id, b.payment_status 
                  FROM bookings b 
                  WHERE b.id = ? AND b.user_id = ? AND b.payment_status = 'unpaid'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Get the item subtotal before removing
        $item_sql = "SELECT subtotal FROM booking_items WHERE id = ? AND booking_id = ?";
        $item_stmt = $conn->prepare($item_sql);
        $item_stmt->bind_param("ii", $booking_item_id, $booking_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $item_data = $item_result->fetch_assoc();
        
        if ($item_data) {
            // Delete the item
            $delete_sql = "DELETE FROM booking_items WHERE id = ? AND booking_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $booking_item_id, $booking_id);
            
            if ($delete_stmt->execute()) {
                // Update the total amount in bookings table
                $update_sql = "UPDATE bookings 
                              SET total_amount = (SELECT COALESCE(SUM(subtotal), 0) FROM booking_items WHERE booking_id = ?) 
                              WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $booking_id, $booking_id);
                $update_stmt->execute();
                
                // Check if booking has no items left, delete the booking
                $check_items_sql = "SELECT COUNT(*) as item_count FROM booking_items WHERE booking_id = ?";
                $check_items_stmt = $conn->prepare($check_items_sql);
                $check_items_stmt->bind_param("i", $booking_id);
                $check_items_stmt->execute();
                $items_count_result = $check_items_stmt->get_result();
                $items_count = $items_count_result->fetch_assoc();
                
                if ($items_count['item_count'] == 0) {
                    $delete_booking_sql = "DELETE FROM bookings WHERE id = ?";
                    $delete_booking_stmt = $conn->prepare($delete_booking_sql);
                    $delete_booking_stmt->bind_param("i", $booking_id);
                    $delete_booking_stmt->execute();
                }
            }
        }
    }
    
    // Redirect to MyProducts.php to refresh
    header("Location: MyProducts.php");
    exit();
}

// Handle bulk payment setup - store booking IDs in session and redirect to payment screen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_payment'])) {
    // Get all unpaid bookings for this user
    $unpaid_sql = "SELECT id, booking_number, total_amount FROM bookings WHERE user_id = ? AND payment_status = 'unpaid' AND booking_status != 'cancelled'";
    $unpaid_stmt = $conn->prepare($unpaid_sql);
    $unpaid_stmt->bind_param("i", $user_id);
    $unpaid_stmt->execute();
    $unpaid_result = $unpaid_stmt->get_result();
    
    $unpaid_bookings = [];
    $total_amount = 0;
    while ($row = $unpaid_result->fetch_assoc()) {
        $unpaid_bookings[] = $row;
        $total_amount += $row['total_amount'];
    }
    
    if (!empty($unpaid_bookings)) {
        // Store bulk payment data in session
        $_SESSION['bulk_payment_data'] = [
            'is_bulk' => true,
            'booking_ids' => array_column($unpaid_bookings, 'id'),
            'booking_numbers' => array_column($unpaid_bookings, 'booking_number'),
            'total_amount' => $total_amount,
            'booking_count' => count($unpaid_bookings)
        ];
        
        // Also store products data for all unpaid bookings
        $all_products = [];
        foreach ($unpaid_bookings as $booking) {
            $items_stmt = $conn->prepare("SELECT product_id, product_name, quantity, subtotal FROM booking_items WHERE booking_id = ?");
            $items_stmt->bind_param("i", $booking['id']);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            while ($item = $items_result->fetch_assoc()) {
                $all_products[] = $item;
            }
            $items_stmt->close();
        }
        $_SESSION['bulk_payment_products'] = $all_products;
        
        // Redirect to payment details page
        header("Location: payment_details.php?bulk_payment=1");
        exit();
    } else {
        $_SESSION['payment_error'] = "No unpaid bookings found.";
        header("Location: MyProducts.php");
        exit();
    }
}

// Handle single booking payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['single_payment'])) {
    $booking_id = $_POST['booking_id'];
    
    // Get the booking details
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
        
        $products = [];
        while ($item = $items_result->fetch_assoc()) {
            $products[] = $item;
        }
        $items_stmt->close();
        
        // Store single payment data in session
        $_SESSION['single_payment_data'] = [
            'booking_id' => $booking_id,
            'booking_number' => $booking_data['booking_number'],
            'total_amount' => $booking_data['total_amount'],
            'products' => $products
        ];
        
        // Redirect to payment details page
        header("Location: payment_details.php?single_payment=1");
        exit();
    }
    $booking_stmt->close();
}

// Fetch bookings for this user only
$bookings_sql = "SELECT b.*, 
                    (SELECT COUNT(*) FROM booking_items WHERE booking_id = b.id) as item_count
                 FROM bookings b 
                 WHERE b.user_id = ? 
                 ORDER BY b.booking_date DESC";

$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

$bookings = [];
$unpaidBookingsCount = 0;
$unpaidBookingsTotal = 0;
while ($row = $bookings_result->fetch_assoc()) {
    // Get booking items for each booking, along with the product image
    $items_stmt = $conn->prepare("SELECT bi.*, p.image_url AS product_image 
                                   FROM booking_items bi 
                                   LEFT JOIN products p ON bi.product_id = p.id 
                                   WHERE bi.booking_id = ?");
    $items_stmt->bind_param("i", $row['id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $row['items'] = [];
    while ($item = $items_result->fetch_assoc()) {
        $row['items'][] = $item;
    }
    $items_stmt->close();
    $bookings[] = $row;
    
    // Count unpaid bookings
    if ($row['payment_status'] === 'unpaid' && $row['booking_status'] !== 'cancelled' && !empty($row['items'])) {
        $unpaidBookingsCount++;
        $unpaidBookingsTotal += $row['total_amount'];
    }
}

// Calculate statistics
$totalBookings = count($bookings);
$pendingBookings = 0;
$confirmedBookings = 0;
$completedBookings = 0;
$cancelledBookings = 0;

foreach ($bookings as $booking) {
    switch ($booking['booking_status']) {
        case 'pending':
            $pendingBookings++;
            break;
        case 'confirmed':
            $confirmedBookings++;
            break;
        case 'completed':
            $completedBookings++;
            break;
        case 'cancelled':
            $cancelledBookings++;
            break;
    }
}

// Helper function to get booking status badge
function getBookingStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge badge-pending">⏳ Pending</span>';
        case 'confirmed':
            return '<span class="badge badge-confirmed">✅ Confirmed</span>';
        case 'completed':
            return '<span class="badge badge-completed">🎉 Completed</span>';
        case 'cancelled':
            return '<span class="badge badge-cancelled">❌ Cancelled</span>';
        default:
            return '<span class="badge">❓ ' . htmlspecialchars($status) . '</span>';
    }
}

// Helper function to get payment status badge
function getPaymentStatusBadge($status) {
    switch ($status) {
        case 'unpaid':
            return '<span class="badge badge-unpaid">💳 Unpaid</span>';
        case 'paid':
            return '<span class="badge badge-paid">✅ Paid</span>';
        case 'failed':
            return '<span class="badge badge-failed">❌ Failed</span>';
        default:
            return '<span class="badge">❓ ' . htmlspecialchars($status) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | JosLee Crocs Dashboard</title>
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
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Section */
        .page-header {
            background: white;
            border-radius: 24px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .title-section h1 {
            font-size: 1.8rem;
            color: #2d3e2b;
            margin-bottom: 0.25rem;
        }

        .title-section p {
            color: #6b5b4b;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #f0f0f0;
            padding: 0.5rem 1rem;
            border-radius: 40px;
        }

        .user-info span {
            color: #2d3e2b;
            font-weight: 600;
        }

        .btn-back {
            background: #8b7355;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: #6b5340;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem 1.8rem;
            flex: 1;
            min-width: 160px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-left: 5px solid;
        }

        .stat-card.total {
            border-left-color: #8e735b;
        }
        .stat-card.pending {
            border-left-color: #f39c12;
        }
        .stat-card.confirmed {
            border-left-color: #27ae60;
        }
        .stat-card.completed {
            border-left-color: #3498db;
        }
        .stat-card.cancelled {
            border-left-color: #e74c3c;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3e2b;
        }

        .stat-label {
            color: #6b5b4b;
            margin-top: 0.3rem;
        }

        /* Bookings Grid */
        .bookings-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .booking-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }

        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .booking-number {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .booking-date {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .booking-body {
            padding: 1.5rem;
        }

        .booking-items {
            margin-bottom: 1.5rem;
        }

        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }

        .booking-item:last-child {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .item-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
            flex-shrink: 0;
            background: #f5f0e8;
        }

        .item-name {
            font-weight: 500;
            color: #333;
        }

        .item-price {
            color: #8b7355;
            font-weight: 600;
        }

        .booking-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #f0f0f0;
        }

        .total-amount {
            font-size: 1.3rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.9rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3e0;
            color: #e67e22;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-unpaid {
            background: #ffeaa7;
            color: #d63031;
        }

        .badge-paid {
            background: #d4edda;
            color: #155724;
        }

        .badge-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 32px;
            color: #a09283;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-sm {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-sm:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .btn-pay {
            background: #e67e22;
            color: white;
        }
        
        .btn-pay:hover {
            background: #d35400;
        }

        .btn-remove {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-remove:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }

        .item-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .btn-bulk-pay {
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-bulk-pay:hover {
            background: #219a52;
            transform: translateY(-2px);
        }
        
        .btn-bulk-pay:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .bulk-payment-section {
            margin-bottom: 1.5rem;
            text-align: right;
        }
        
        .bulk-summary {
            background: linear-gradient(135deg, #27ae60 0%, #219a52 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .bulk-summary span {
            font-weight: bold;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            .booking-header {
                flex-direction: column;
                text-align: center;
            }
            .booking-footer {
                flex-direction: column;
                text-align: center;
            }
            .bulk-payment-section {
                text-align: center;
            }
            .bulk-summary {
                flex-direction: column;
                text-align: center;
            }
            .item-thumb {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="page-header">
        <div class="title-section">
            <h1>📋 My Bookings</h1>
            <p>Track your booking history and payment status</p>
            <div class="user-info" style="margin-top: 10px;">
                <span>👤 Logged in as: <?php echo htmlspecialchars($username); ?></span>
            </div>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="Dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Bulk Payment Section -->
    <?php if ($unpaidBookingsCount > 0): ?>
    <div class="bulk-payment-section">
        <form method="POST" action="payment_details.php">
            <input type="hidden" name="is_bulk_payment" value="1">
            <input type="hidden" name="bulk_booking_ids" value='<?php echo json_encode(array_column(array_filter($bookings, function($b) { return $b['payment_status'] === 'unpaid' && $b['booking_status'] !== 'cancelled' && !empty($b['items']); }), 'id')); ?>'>
            <input type="hidden" name="total_amount" value="<?php echo $unpaidBookingsTotal; ?>">
            <input type="hidden" name="products_data" value='<?php 
                // Collect all products from unpaid bookings
                $all_products = array();
                foreach($bookings as $b) {
                    if($b['payment_status'] === 'unpaid' && $b['booking_status'] !== 'cancelled' && !empty($b['items'])) {
                        foreach($b['items'] as $item) {
                            $all_products[] = array(
                                'product_id' => $item['product_id'],
                                'product_name' => $item['product_name'],
                                'quantity' => $item['quantity'],
                                'subtotal' => $item['subtotal']
                            );
                        }
                    }
                }
                echo htmlspecialchars(json_encode($all_products));
            ?>'>
            <button type="submit" class="btn-bulk-pay">
                💰 Complete Payment for All Unpaid Bookings (<?php echo $unpaidBookingsCount; ?>)
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Stats Summary -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $totalBookings; ?></div>
            <div class="stat-label">📦 Total Bookings</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number"><?php echo $pendingBookings; ?></div>
            <div class="stat-label">⏳ Pending</div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-number"><?php echo $confirmedBookings; ?></div>
            <div class="stat-label">✅ Confirmed</div>
        </div>
        <div class="stat-card completed">
            <div class="stat-number"><?php echo $completedBookings; ?></div>
            <div class="stat-label">🎉 Completed</div>
        </div>
        <div class="stat-card cancelled">
            <div class="stat-number"><?php echo $cancelledBookings; ?></div>
            <div class="stat-label">❌ Cancelled</div>
        </div>
    </div>

    <!-- Bookings List -->
    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <h3>✨ No bookings found</h3>
            <p>You haven't made any bookings yet. Visit our store to place an order!</p>
            <p style="margin-top: 1rem;">
                <a href="Service.php" style="color: #e67e22;">Browse Products →</a>
            </p>
        </div>
    <?php else: ?>
        <div class="bookings-grid">
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <div>
                            <div class="booking-number">📄 Booking #: <?php echo htmlspecialchars($booking['booking_number']); ?></div>
                            <div class="booking-date">📅 <?php echo date('F d, Y h:i A', strtotime($booking['booking_date'])); ?></div>
                        </div>
                        <div class="status-badges">
                            <?php echo getBookingStatusBadge($booking['booking_status']); ?>
                            <?php echo getPaymentStatusBadge($booking['payment_status']); ?>
                        </div>
                    </div>
                    <div class="booking-body">
                        <div class="booking-items">
                            <?php foreach ($booking['items'] as $item): ?>
                                <div class="booking-item">
                                    <div class="item-info">
                                        <?php 
                                            $imgSrc = !empty($item['product_image']) ? $item['product_image'] : 'images/placeholder.png';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="item-thumb"
                                             onerror="this.src='images/placeholder.png';">
                                        <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?></span>
                                    </div>
                                    <div class="item-actions">
                                        <span class="item-price"><?php echo number_format($item['subtotal'], 0); ?> Rwf</span>
                                        <?php if ($booking['payment_status'] === 'unpaid'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this item from your booking?');">
                                                <input type="hidden" name="booking_item_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="remove_item" class="btn-remove">✕ Remove</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="booking-footer">
                            <div class="total-amount">
                                Total: <?php echo number_format($booking['total_amount'], 0); ?> Rwf
                            </div>
                            <?php if ($booking['payment_status'] === 'unpaid' && !empty($booking['items'])): ?>
                                <form method="POST" action="payment_details.php" style="display: inline;">
                                    <input type="hidden" name="single_payment" value="1">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" class="btn-sm btn-pay">
                                        💳 Complete Payment
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($booking['notes']): ?>
                                <div style="font-size: 0.8rem; color: #666;">
                                    📝 Note: <?php echo htmlspecialchars($booking['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
