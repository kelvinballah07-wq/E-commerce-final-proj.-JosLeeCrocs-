<?php
// Start session and check if user is admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Dashboard.php");
    exit();
}

require_once 'Connection.php';

// Handle product status update
$updateMessage = '';
$updateError = '';

if (isset($_POST['update_status']) && isset($_POST['product_id']) && isset($_POST['new_status'])) {
    $product_id = intval($_POST['product_id']);
    $new_status = $_POST['new_status'];
    
    // Validate status
    $allowed_statuses = ['pending_payment', 'purchased', 'cancelled', 'shipped'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $product_id);
        
        if ($update_stmt->execute()) {
            $updateMessage = "Product status updated successfully!";
            $updateMessage_type = "success";
        } else {
            $updateError = "Error updating product status: " . $conn->error;
            $updateMessage_type = "error";
        }
        $update_stmt->close();
    }
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // Get product name first
    $check_stmt = $conn->prepare("SELECT product_name FROM products WHERE id = ?");
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $product_name = $product['product_name'];
        
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->bind_param("i", $product_id);
        
        if ($delete_stmt->execute()) {
            $updateMessage = "Product '" . htmlspecialchars($product_name) . "' has been deleted successfully!";
            $updateMessage_type = "success";
        } else {
            $updateError = "Error deleting product: " . $conn->error;
            $updateMessage_type = "error";
        }
        $delete_stmt->close();
    }
    $check_stmt->close();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query based on filters
$sql = "SELECT p.*, u.username, u.email 
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        WHERE 1=1";

if ($status_filter != 'all') {
    $sql .= " AND p.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $sql .= " AND (p.product_name LIKE '%$search_term%' 
              OR u.username LIKE '%$search_term%'
              OR p.product_type LIKE '%$search_term%')";
}

$sql .= " ORDER BY 
          CASE p.status 
              WHEN 'pending_payment' THEN 1 
              WHEN 'purchased' THEN 2 
              WHEN 'shipped' THEN 3 
              WHEN 'cancelled' THEN 4 
          END, 
          p.date_ordered DESC";

$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$pending_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'pending_payment'")->fetch_assoc()['count'];
$purchased_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'purchased'")->fetch_assoc()['count'];
$shipped_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'shipped'")->fetch_assoc()['count'];
$cancelled_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'cancelled'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel | JosLee Crocs</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 {
            color: #333;
            font-size: 1.8rem;
        }

        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            margin-left: 10px;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
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
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card.pending { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); }
        .stat-card.purchased { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); }
        .stat-card.shipped { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .stat-card.cancelled { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .stat-card.total { background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%); }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .stat-label {
            margin-top: 5px;
            opacity: 0.9;
            font-size: 0.8rem;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Search and Filter */
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-box select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
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

        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        select.status-select {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 0.8rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .small-btn {
            padding: 5px 10px;
            font-size: 0.75rem;
            border-radius: 5px;
        }

        footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            th, td {
                padding: 8px;
                font-size: 0.8rem;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📦 Manage Products <span class="admin-badge">Admin Only</span></h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! | Manage all products and their payment status</p>
            </div>
            <div class="header-actions">
                <a href="upload_product.php" class="btn btn-success">➕ Add New Product</a>
                <a href="admin_dashboard.php" class="btn btn-primary">📊 Admin Dashboard</a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card total" onclick="filterByStatus('all')">
                <div class="stat-number"><?php echo $total_products; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card pending" onclick="filterByStatus('pending_payment')">
                <div class="stat-number"><?php echo $pending_products; ?></div>
                <div class="stat-label">⏳ Pending Payment</div>
            </div>
            <div class="stat-card purchased" onclick="filterByStatus('purchased')">
                <div class="stat-number"><?php echo $purchased_products; ?></div>
                <div class="stat-label">✅ Purchased</div>
            </div>
            <div class="stat-card shipped" onclick="filterByStatus('shipped')">
                <div class="stat-number"><?php echo $shipped_products; ?></div>
                <div class="stat-label">🚚 Shipped</div>
            </div>
            <div class="stat-card cancelled" onclick="filterByStatus('cancelled')">
                <div class="stat-number"><?php echo $cancelled_products; ?></div>
                <div class="stat-label">❌ Cancelled</div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($updateMessage): ?>
            <div class="alert alert-success">✅ <?php echo $updateMessage; ?></div>
        <?php endif; ?>
        
        <?php if ($updateError): ?>
            <div class="alert alert-danger">❌ <?php echo $updateError; ?></div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="filter-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search by product name, user, or type..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       onkeyup="applyFilters()">
            </div>
            <div class="filter-box">
                <select id="statusFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending_payment" <?php echo $status_filter == 'pending_payment' ? 'selected' : ''; ?>>Pending Payment</option>
                    <option value="purchased" <?php echo $status_filter == 'purchased' ? 'selected' : ''; ?>>Purchased</option>
                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <a href="manage_products.php" class="btn btn-secondary">Reset Filters</a>
        </div>

        <!-- Products Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Date Ordered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                📭 No products found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img class="product-image" 
                                         src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://placehold.co/400x300?text=No+Image'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars(substr($product['product_description'] ?? 'No description', 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($product['username']); ?><br>
                                    <small><?php echo htmlspecialchars($product['email'] ?? 'No email'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($product['product_type'] ?? 'Knitted Item'); ?></td>
                                <td><strong><?php echo number_format($product['price'], 0); ?> Rwf</strong></td>
                                <td><?php echo $product['quantity'] ?? 1; ?></td>
                                <td><?php echo date('M d, Y', strtotime($product['date_ordered'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <select name="new_status" class="status-select" onchange="this.form.submit()">
                                            <option value="pending_payment" <?php echo $product['status'] == 'pending_payment' ? 'selected' : ''; ?>>⏳ Pending Payment</option>
                                            <option value="purchased" <?php echo $product['status'] == 'purchased' ? 'selected' : ''; ?>>✅ Purchased</option>
                                            <option value="shipped" <?php echo $product['status'] == 'shipped' ? 'selected' : ''; ?>>🚚 Shipped</option>
                                            <option value="cancelled" <?php echo $product['status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                    <span class="status-badge status-<?php echo $product['status']; ?>">
                                        <?php 
                                        switch($product['status']) {
                                            case 'pending_payment': echo '⏳ Pending'; break;
                                            case 'purchased': echo '✅ Purchased'; break;
                                            case 'shipped': echo '🚚 Shipped'; break;
                                            case 'cancelled': echo '❌ Cancelled'; break;
                                            default: echo $product['status'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-info small-btn">View</a>
                                    <a href="manage_products.php?delete=<?php echo $product['id']; ?>" 
                                       class="btn btn-danger small-btn" 
                                       onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone!')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer>
            <p>&copy; 2025 JosLee Crocs | Admin Panel - Product Management</p>
        </footer>
    </div>

    <script>
        function filterByStatus(status) {
            window.location.href = 'manage_products.php?status=' + status;
        }
        
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            window.location.href = 'manage_products.php?status=' + status + '&search=' + encodeURIComponent(search);
        }
        
        // Auto-submit when status is changed
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
