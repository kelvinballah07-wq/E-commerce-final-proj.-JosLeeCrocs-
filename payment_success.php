<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

$transaction = isset($_SESSION['last_transaction']) ? $_SESSION['last_transaction'] : null;

if (!$transaction) {
    header("Location: MyProducts.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - JosLee Crocs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }

        h1 {
            color: #28a745;
            margin-bottom: 20px;
        }

        .transaction-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }
        
        .bulk-badge {
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✅</div>
        <?php if (isset($transaction['is_bulk']) && $transaction['is_bulk']): ?>
            <div class="bulk-badge">💰 Bulk Payment</div>
            <h1>Payment Successful!</h1>
            <p>You have successfully completed payment for <?php echo $transaction['booking_count']; ?> booking(s).</p>
        <?php else: ?>
            <h1>Payment Successful!</h1>
            <p>Thank you for your purchase. Your payment has been processed successfully.</p>
        <?php endif; ?>
        
        <div class="transaction-details">
            <h3>Transaction Details</h3>
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($transaction['transaction_id']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($transaction['date']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo ucfirst(htmlspecialchars($transaction['payment_method'])); ?></p>
            <p><strong>Total Amount:</strong> <?php echo number_format($transaction['total_amount'], 0); ?> Rwf</p>
            
            <?php if (isset($transaction['is_bulk']) && $transaction['is_bulk'] && isset($transaction['bookings'])): ?>
                <h4 style="margin-top: 15px;">Bookings Completed:</h4>
                <?php foreach ($transaction['bookings'] as $booking): ?>
                    <p>• Booking #: <?php echo htmlspecialchars($booking['booking_number']); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="MyProducts.php" class="btn">View My Bookings</a>
        <a href="Service.php" class="btn btn-secondary">Continue Shopping</a>
    </div>
</body>
</html>
<?php
// Clear the transaction from session after displaying
unset($_SESSION['last_transaction']);
?>
