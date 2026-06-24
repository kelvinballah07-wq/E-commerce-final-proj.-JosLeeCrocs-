<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'Connection.php';

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Service.php");
    exit();
}

// Get POST data
$user_id = $_SESSION['user_id'];
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
$password = isset($_POST['password']) ? $_POST['password'] : '';
$is_bulk_payment = isset($_POST['is_bulk_payment']) ? 1 : 0;
$bulk_booking_ids_raw = isset($_POST['bulk_booking_ids']) ? $_POST['bulk_booking_ids'] : '';
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$products_data_raw = isset($_POST['products_data']) ? $_POST['products_data'] : '';
$is_single_payment = isset($_POST['is_single_payment']) ? 1 : 0;
$existing_booking_id = isset($_POST['existing_booking_id']) ? intval($_POST['existing_booking_id']) : 0;

// Use existing booking ID if provided (for single payment from MyProducts.php)
if ($existing_booking_id > 0) {
    $booking_id = $existing_booking_id;
}

// Parse bulk booking IDs
$bulk_booking_ids = array();
if (!empty($bulk_booking_ids_raw)) {
    if (is_string($bulk_booking_ids_raw)) {
        $bulk_booking_ids = json_decode($bulk_booking_ids_raw, true);
        if (!is_array($bulk_booking_ids)) {
            $bulk_booking_ids = array();
        }
    }
}

// Validate payment method
if (empty($payment_method)) {
    $_SESSION['payment_error'] = "Please select a payment method";
    header("Location: payment_details.php");
    exit();
}

// Verify password
if (empty($password)) {
    $_SESSION['payment_error'] = "Please enter your password to confirm payment";
    header("Location: payment_details.php?error=password_required");
    exit();
}

// Verify user password
$password_verified = false;
$verify_sql = "SELECT password FROM users WHERE id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("i", $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($user_data = $verify_result->fetch_assoc()) {
    if (password_verify($password, $user_data['password'])) {
        $password_verified = true;
    } else {
        $_SESSION['payment_error'] = "Incorrect password. Payment cannot be processed.";
        header("Location: payment_details.php?error=incorrect_password");
        exit();
    }
}
$verify_stmt->close();

if (!$password_verified) {
    $_SESSION['payment_error'] = "Password verification failed. Please try again.";
    header("Location: payment_details.php?error=verification_failed");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Process Bulk Payment
    if ($is_bulk_payment == 1 && !empty($bulk_booking_ids)) {
        $transaction_id = 'BULK_' . time() . '_' . rand(1000, 9999);
        $total_bulk_amount = 0;
        $all_bookings_data = array();

        // Process EACH booking in the array
        foreach ($bulk_booking_ids as $bid) {
            // Get booking details
            $booking_sql = "SELECT booking_number FROM bookings WHERE id = ? AND user_id = ? AND payment_status = 'unpaid'";
            $booking_stmt = $conn->prepare($booking_sql);
            $booking_stmt->bind_param("ii", $bid, $user_id);
            $booking_stmt->execute();
            $booking_result = $booking_stmt->get_result();

            if ($booking_data = $booking_result->fetch_assoc()) {
                // Get the items for this booking along with current stock levels
                $items_sql = "SELECT bi.*, p.quantity as stock_qty FROM booking_items bi JOIN products p ON bi.product_id = p.id WHERE bi.booking_id = ?";
                $items_stmt = $conn->prepare($items_sql);
                $items_stmt->bind_param("i", $bid);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();

                $booking_total = 0;

                while ($item = $items_result->fetch_assoc()) {
                    $booking_total += $item['subtotal'];

                    // Update product inventory (reduce quantity)
                    $new_qty = $item['stock_qty'] - $item['quantity'];
                    if ($new_qty < 0) {
                        throw new Exception("Insufficient stock for product: " . $item['product_name']);
                    }
                    $update_product = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                    $update_product->bind_param("ii", $new_qty, $item['product_id']);
                    $update_product->execute();
                    $update_product->close();

                    // Record payment for this line item
                    $payment_stmt = $conn->prepare("INSERT INTO payments (product_id, user_id, amount, quantity, payment_method, transaction_id, payment_status, payment_date, is_bulk_payment, bulk_booking_ids) VALUES (?, ?, ?, ?, ?, ?, 'completed', NOW(), 1, ?)");
                    $payment_stmt->bind_param("iidissi", $item['product_id'], $user_id, $item['subtotal'], $item['quantity'], $payment_method, $transaction_id, $bid);
                    $payment_stmt->execute();
                    $payment_stmt->close();
                }
                $items_stmt->close();

                $total_bulk_amount += $booking_total;

                // Update this booking to paid status
                $update_booking = $conn->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid' WHERE id = ? AND user_id = ?");
                $update_booking->bind_param("ii", $bid, $user_id);
                $update_booking->execute();
                $update_booking->close();

                $all_bookings_data[] = array(
                    'booking_id' => $bid,
                    'booking_number' => $booking_data['booking_number'],
                    'total' => $booking_total
                );
            }
            $booking_stmt->close();
        }

        // Store transaction in session for success page
        $_SESSION['last_transaction'] = array(
            'transaction_id' => $transaction_id,
            'total_amount' => $total_bulk_amount,
            'payment_method' => $payment_method,
            'date' => date('Y-m-d H:i:s'),
            'is_bulk' => true,
            'booking_count' => count($bulk_booking_ids),
            'bookings' => $all_bookings_data
        );

        // Commit transaction
        $conn->commit();

        // Clear bulk payment session data
        unset($_SESSION['bulk_payment_data']);

        header("Location: payment_success.php");
        exit();
    }
    // Process Single Payment - Update existing booking
    elseif ($booking_id > 0) {
        $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);

        // First, check if booking exists and is unpaid
        $check_booking_sql = "SELECT id, booking_number FROM bookings WHERE id = ? AND user_id = ? AND payment_status = 'unpaid'";
        $check_booking_stmt = $conn->prepare($check_booking_sql);
        $check_booking_stmt->bind_param("ii", $booking_id, $user_id);
        $check_booking_stmt->execute();
        $check_booking_result = $check_booking_stmt->get_result();

        if ($check_booking_data = $check_booking_result->fetch_assoc()) {
            // Update booking to paid
            $update_booking = $conn->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid' WHERE id = ? AND user_id = ?");
            $update_booking->bind_param("ii", $booking_id, $user_id);
            $update_booking->execute();
            $update_booking->close();

            // Get products from booking items and update inventory
            $items_sql = "SELECT bi.*, p.quantity as stock_qty FROM booking_items bi JOIN products p ON bi.product_id = p.id WHERE bi.booking_id = ?";
            $items_stmt = $conn->prepare($items_sql);
            $items_stmt->bind_param("i", $booking_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();

            $products_data = array();
            while ($item = $items_result->fetch_assoc()) {
                $products_data[] = $item;

                // Update product inventory (reduce quantity)
                $new_qty = $item['stock_qty'] - $item['quantity'];
                if ($new_qty < 0) {
                    throw new Exception("Insufficient stock for product: " . $item['product_name']);
                }
                $update_product = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $update_product->bind_param("ii", $new_qty, $item['product_id']);
                $update_product->execute();
                $update_product->close();

                // Record payment
                $payment_stmt = $conn->prepare("INSERT INTO payments (product_id, user_id, amount, quantity, payment_method, transaction_id, payment_status, payment_date, is_bulk_payment, bulk_booking_ids) VALUES (?, ?, ?, ?, ?, ?, 'completed', NOW(), 0, NULL)");
                $payment_stmt->bind_param("iidiss", $item['product_id'], $user_id, $item['subtotal'], $item['quantity'], $payment_method, $transaction_id);
                $payment_stmt->execute();
                $payment_stmt->close();
            }
            $items_stmt->close();

            // Store transaction in session
            $_SESSION['last_transaction'] = array(
                'transaction_id' => $transaction_id,
                'total_amount' => $total_amount,
                'payment_method' => $payment_method,
                'date' => date('Y-m-d H:i:s'),
                'products' => $products_data,
                'booking_id' => $booking_id,
                'booking_number' => $check_booking_data['booking_number'],
                'is_bulk' => false
            );

            $conn->commit();

            // Clear single payment session data
            unset($_SESSION['single_payment_data']);
            unset($_SESSION['selected_products']);
            unset($_SESSION['current_booking_id']);

            header("Location: payment_success.php");
            exit();
        } else {
            throw new Exception("Booking not found or already paid");
        }
        $check_booking_stmt->close();
    }
    // Process new booking from product page (no existing booking_id)
    elseif (!empty($products_data_raw)) {
        $products_data = json_decode($products_data_raw, true);

        if (empty($products_data)) {
            throw new Exception("No products data received");
        }

        // Create a new booking
        $booking_number = 'JLC-' . strtoupper(uniqid());
        $insert_booking = $conn->prepare("INSERT INTO bookings (booking_number, user_id, total_amount, booking_status, payment_status, booking_date) VALUES (?, ?, ?, 'confirmed', 'paid', NOW())");
        $insert_booking->bind_param("sid", $booking_number, $user_id, $total_amount);
        $insert_booking->execute();
        $booking_id = $insert_booking->insert_id;
        $insert_booking->close();

        // Insert booking items
        $insert_item = $conn->prepare("INSERT INTO booking_items (booking_id, product_id, product_name, quantity, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($products_data as $product) {
            $subtotal = $product['price'] * $product['quantity'];
            $insert_item->bind_param("iisid", $booking_id, $product['id'], $product['name'], $product['quantity'], $subtotal);
            $insert_item->execute();

            // Update product inventory
            $stock_stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
            $stock_stmt->bind_param("i", $product['id']);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();
            $stock_data = $stock_result->fetch_assoc();

            if ($stock_data) {
                $new_qty = $stock_data['quantity'] - $product['quantity'];
                if ($new_qty < 0) {
                    throw new Exception("Insufficient stock for product: " . $product['name']);
                }
                $update_product = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $update_product->bind_param("ii", $new_qty, $product['id']);
                $update_product->execute();
                $update_product->close();
            }
            $stock_stmt->close();
        }
        $insert_item->close();

        // Record payment
        $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);

        foreach ($products_data as $product) {
            $subtotal = $product['price'] * $product['quantity'];
            $payment_stmt = $conn->prepare("INSERT INTO payments (product_id, user_id, amount, quantity, payment_method, transaction_id, payment_status, payment_date, is_bulk_payment, bulk_booking_ids) VALUES (?, ?, ?, ?, ?, ?, 'completed', NOW(), 0, NULL)");
            $payment_stmt->bind_param("iidiss", $product['id'], $user_id, $subtotal, $product['quantity'], $payment_method, $transaction_id);
            $payment_stmt->execute();
            $payment_stmt->close();
        }

        // Store transaction in session
        $_SESSION['last_transaction'] = array(
            'transaction_id' => $transaction_id,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method,
            'date' => date('Y-m-d H:i:s'),
            'products' => $products_data,
            'booking_id' => $booking_id,
            'booking_number' => $booking_number,
            'is_bulk' => false
        );

        $conn->commit();
        header("Location: payment_success.php");
        exit();
    } else {
        throw new Exception("No booking selected for payment");
    }

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['payment_error'] = "Payment failed: " . $e->getMessage();
    header("Location: payment_failed.php");
    exit();
}

$conn->close();
?>
