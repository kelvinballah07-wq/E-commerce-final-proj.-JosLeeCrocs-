<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'Connection.php';

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Dashboard.php");
    exit();
}

$username = $_SESSION['username'];

// Get sales with user info ONLY (no payments join)
$sql = "SELECT b.*, u.username as customer_name, u.email as customer_email
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.payment_status = 'paid' 
        ORDER BY b.booking_date DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$sales = [];
while($row = $result->fetch_assoc()) {
    // Get items for this booking
    $items_query = "SELECT * FROM booking_items WHERE booking_id = " . $row['id'];
    $items_result = $conn->query($items_query);
    
    $items = [];
    if ($items_result) {
        while($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
    }
    $row['items'] = $items;
    $sales[] = $row;
}

// Calculate totals
$total_sales_amount = 0;
$total_items_sold = 0;
$total_transactions = count($sales);

foreach($sales as $sale) {
    $total_sales_amount += floatval($sale['total_amount']);
    foreach($sale['items'] as $item) {
        $total_items_sold += intval($item['quantity']);
    }
}

// Helper function for customer name
function getCustomerName($sale) {
    if (!empty($sale['customer_name'])) {
        return $sale['customer_name'];
    }
    if (!empty($sale['user_name'])) {
        return $sale['user_name'];
    }
    return 'Guest';
}

function getCustomerEmail($sale) {
    if (!empty($sale['customer_email'])) {
        return $sale['customer_email'];
    }
    if (!empty($sale['user_email'])) {
        return $sale['user_email'];
    }
    return 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Records | JosLee Crocs Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
            flex-wrap: wrap;
        }
        h1 { color: #333; font-size: 1.8rem; }
        h1 i { color: #667eea; }
        .btn-back {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number { font-size: 2rem; font-weight: bold; }
        .stat-label { margin-top: 8px; font-size: 0.85rem; opacity: 0.9; }
        .section-card {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-input {
            padding: 10px 20px;
            border: 1px solid #dee2e6;
            border-radius: 30px;
            outline: none;
            flex: 1;
            min-width: 200px;
        }
        .filter-input:focus {
            border-color: #667eea;
        }
        .export-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .export-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .clear-btn {
            background: #6c757d;
        }
        .clear-btn:hover {
            background: #5a6268;
        }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        tr:hover { background: #f1f3f5; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .empty-state { 
            text-align: center; 
            padding: 60px; 
            color: #999;
        }
        .empty-state i { font-size: 48px; margin-bottom: 15px; color: #ddd; }
        @media (max-width: 768px) { 
            body { padding: 10px; } 
            .container { padding: 15px; } 
            th, td { padding: 8px; font-size: 12px; }
            .stat-number { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fas fa-chart-line"></i> Sales Records</h1>
            <p style="color: #666; margin-top: 5px;">Complete sales history from all paid bookings</p>
            <div style="margin-top: 8px;">
                <i class="fas fa-user-shield"></i> Admin: <?php echo htmlspecialchars($username); ?>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="export-btn" onclick="exportToCSV()">
                <i class="fas fa-download"></i> Export CSV
            </button>
            <a href="admin_dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($total_sales_amount, 0); ?> RWF</div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_transactions; ?></div>
            <div class="stat-label">Transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_items_sold; ?></div>
            <div class="stat-label">Items Sold</div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title">
            <i class="fas fa-list"></i> All Sales Records
        </div>
        <div class="filter-bar">
            <input type="text" id="searchInput" class="filter-input" 
                   placeholder="🔍 Search by customer or booking number...">
            <input type="date" id="dateFilter" class="filter-input">
            <button class="export-btn clear-btn" onclick="clearFilters()">
                <i class="fas fa-eraser"></i> Clear Filters
            </button>
        </div>
        <div class="table-container">
            <table id="salesTable">
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-receipt"></i><br>
                                No sales records found yet.<br>
                                <small>Only bookings with 'paid' status appear here.</small>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr data-booking="<?php echo htmlspecialchars($sale['booking_number']); ?>" 
                                data-customer="<?php echo htmlspecialchars(getCustomerName($sale)); ?>" 
                                data-date="<?php echo date('Y-m-d', strtotime($sale['booking_date'])); ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($sale['booking_number']); ?></strong>
                                    <br><small>#<?php echo $sale['id']; ?></small>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($sale['booking_date'])); ?>
                                    <br><small><?php echo date('h:i A', strtotime($sale['booking_date'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(getCustomerName($sale)); ?></strong>
                                    <br><small><?php echo htmlspecialchars(getCustomerEmail($sale)); ?></small>
                                </td>
                                <td>
                                    <?php foreach ($sale['items'] as $item): ?>
                                        <div>
                                            <?php echo htmlspecialchars($item['product_name']); ?> 
                                            × <?php echo $item['quantity']; ?>
                                            <small>(<?php echo number_format($item['subtotal'], 0); ?> RWF)</small>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <strong style="color: #28a745;"><?php echo number_format($sale['total_amount'], 0); ?> RWF</strong>
                                </td>
                                <td>
                                    <span class="badge badge-paid">
                                        <i class="fas fa-check-circle"></i> 
                                        <?php echo ucfirst($sale['payment_status'] ?? 'Paid'); ?>
                                    </span>
                                    <br>
                                    <span class="badge badge-confirmed">
                                        <i class="fas fa-check"></i>
                                        <?php echo ucfirst($sale['booking_status'] ?? 'Confirmed'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Search and filter functionality
const searchInput = document.getElementById('searchInput');
const dateFilter = document.getElementById('dateFilter');
const rows = document.querySelectorAll('#salesTable tbody tr');

function filterTable() {
    const search = searchInput.value.toLowerCase();
    const date = dateFilter.value;
    
    rows.forEach(row => {
        if (row.querySelector('.empty-state')) return;
        
        const booking = row.getAttribute('data-booking')?.toLowerCase() || '';
        const customer = row.getAttribute('data-customer')?.toLowerCase() || '';
        const rowDate = row.getAttribute('data-date') || '';
        
        const matchSearch = !search || booking.includes(search) || customer.includes(search);
        const matchDate = !date || rowDate === date;
        
        row.style.display = (matchSearch && matchDate) ? '' : 'none';
    });
}

function clearFilters() {
    searchInput.value = '';
    dateFilter.value = '';
    filterTable();
}

searchInput.addEventListener('keyup', filterTable);
dateFilter.addEventListener('change', filterTable);

// Export to CSV
function exportToCSV() {
    let csv = "Booking #,Date,Time,Customer,Email,Product Name,Quantity,Subtotal (RWF),Total Amount (RWF),Status\n";
    
    <?php foreach ($sales as $sale): ?>
        <?php if (empty($sale['items'])): ?>
            csv += `"<?php echo addslashes($sale['booking_number']); ?>",`;
            csv += `"<?php echo date('Y-m-d', strtotime($sale['booking_date'])); ?>",`;
            csv += `"<?php echo date('H:i:s', strtotime($sale['booking_date'])); ?>",`;
            csv += `"<?php echo addslashes(getCustomerName($sale)); ?>",`;
            csv += `"<?php echo addslashes(getCustomerEmail($sale)); ?>",`;
            csv += `"No items",0,0,`;
            csv += `"<?php echo number_format($sale['total_amount'], 0); ?>",`;
            csv += `"<?php echo $sale['payment_status']; ?>"\n`;
        <?php else: ?>
            <?php foreach ($sale['items'] as $item): ?>
                csv += `"<?php echo addslashes($sale['booking_number']); ?>",`;
                csv += `"<?php echo date('Y-m-d', strtotime($sale['booking_date'])); ?>",`;
                csv += `"<?php echo date('H:i:s', strtotime($sale['booking_date'])); ?>",`;
                csv += `"<?php echo addslashes(getCustomerName($sale)); ?>",`;
                csv += `"<?php echo addslashes(getCustomerEmail($sale)); ?>",`;
                csv += `"<?php echo addslashes($item['product_name']); ?>",`;
                csv += `"<?php echo $item['quantity']; ?>",`;
                csv += `"<?php echo number_format($item['subtotal'], 0); ?>",`;
                csv += `"<?php echo number_format($sale['total_amount'], 0); ?>",`;
                csv += `"<?php echo $sale['payment_status']; ?>"\n`;
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    
    // Create and download CSV file
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', 'sales_records_' + new Date().toISOString().slice(0,19) + '.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>
