<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

require_once 'Connection.php';

$user_id = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch bookings for the user
if ($isAdmin) {
    // Admin sees all bookings
    $sql = "SELECT b.*, u.username 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            ORDER BY b.booking_date DESC";
    $result = $conn->query($sql);
} else {
    // User sees only their bookings
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$bookings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get booking items for each booking
        $items_stmt = $conn->prepare("SELECT * FROM booking_items WHERE booking_id = ?");
        $items_stmt->bind_param("i", $row['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $row['items'] = [];
        while ($item = $items_result->fetch_assoc()) {
            $row['items'][] = $item;
        }
        $bookings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - JosLee Crocs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 10px; }
        .booking-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #6a11cb;
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .status-pending { color: #856404; background: #fff3cd; padding: 3px 10px; border-radius: 20px; }
        .status-confirmed { color: #155724; background: #d4edda; padding: 3px 10px; border-radius: 20px; }
        .status-completed { color: #0c5460; background: #d1ecf1; padding: 3px 10px; border-radius: 20px; }
        .status-cancelled { color: #721c24; background: #f8d7da; padding: 3px 10px; border-radius: 20px; }
        .btn { padding: 10px 20px; background: #6a11cb; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #6c757d; }
        @media (max-width: 768px) {
            .container { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 My Bookings</h1>
        <p>View all your booking history</p>
        <a href="services.php" class="btn" style="margin-bottom: 20px;">← Back to Services</a>
        
        <?php if (empty($bookings)): ?>
            <p>No bookings found.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <div>
                            <strong>Booking #:</strong> <?php echo htmlspecialchars($booking['booking_number']); ?>
                        </div>
                        <div>
                            <strong>Date:</strong> <?php echo date('F d, Y H:i', strtotime($booking['booking_date'])); ?>
                        </div>
                        <div>
                            <span class="status-<?php echo $booking['booking_status']; ?>">
                                <?php echo ucfirst($booking['booking_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <?php foreach ($booking['items'] as $item): ?>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                                <span><?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?></span>
                                <span><?php echo number_format($item['subtotal'], 0); ?> Rwf</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 15px; text-align: right;">
                        <strong>Total: <?php echo number_format($booking['total_amount'], 0); ?> Rwf</strong>
                    </div>
                    <div style="margin-top: 10px;">
                        <small>Payment Status: <?php echo ucfirst($booking['payment_status']); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
