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

// Handle user deletion
$deleteMessage = '';
$deleteError = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id_to_delete = intval($_GET['delete']);
    
    // Prevent admin from deleting themselves
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $deleteError = "You cannot delete your own account!";
    } else {
        // Check if user exists
        $checkStmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $user_id_to_delete);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $user_to_delete = $checkResult->fetch_assoc();
            
            // Delete user (products will be deleted due to foreign key cascade)
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $deleteStmt->bind_param("i", $user_id_to_delete);
            
            if ($deleteStmt->execute()) {
                $deleteMessage = "User '" . htmlspecialchars($user_to_delete['username']) . "' has been deleted successfully!";
            } else {
                $deleteError = "Error deleting user: " . $conn->error;
            }
            $deleteStmt->close();
        } else {
            $deleteError = "User not found!";
        }
        $checkStmt->close();
    }
}

// Fetch all users from database
$sql = "SELECT id, username, email, role, created_at FROM users ORDER BY id";
$result = $conn->query($sql);

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get statistics
$totalUsers = count($users);
$adminCount = 0;
$regularUsers = 0;

foreach ($users as $user) {
    if ($user['role'] === 'admin') {
        $adminCount++;
    } else {
        $regularUsers++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel | JosLee Crocs</title>
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
            max-width: 1200px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }

        .stat-label {
            margin-top: 5px;
            opacity: 0.9;
            font-size: 0.9rem;
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
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .user-role {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .role-admin {
            background: #dc3545;
            color: white;
        }

        .role-user {
            background: #28a745;
            color: white;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background 0.3s;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .delete-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background: white;
            margin: 15% auto;
            padding: 25px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .modal-content p {
            margin-bottom: 20px;
            color: #666;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .confirm-delete {
            background: #dc3545;
            color: white;
        }

        .cancel-delete {
            background: #6c757d;
            color: white;
        }

        /* Search Box */
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-box input {
            flex: 1;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Footer */
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
                font-size: 0.85rem;
            }
            .header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>👥 Manage Users <span class="admin-badge">Admin Only</span></h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="btn btn-primary">📊 Admin Dashboard</a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $adminCount; ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $regularUsers; ?></div>
                <div class="stat-label">Regular Users</div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($deleteMessage): ?>
            <div class="alert alert-success">✅ <?php echo $deleteMessage; ?></div>
        <?php endif; ?>
        
        <?php if ($deleteError): ?>
            <div class="alert alert-danger">❌ <?php echo $deleteError; ?></div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Search by username or email..." onkeyup="searchUsers()">
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr data-username="<?php echo strtolower(htmlspecialchars($user['username'])); ?>" 
                                data-email="<?php echo strtolower(htmlspecialchars($user['email'] ?? '')); ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <span style="color: #28a745; font-size: 0.7rem;"> (You)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'Not provided'); ?></td>
                                <td>
                                    <span class="user-role <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                        <?php echo $user['role'] === 'admin' ? '👑 Admin' : '👤 User'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="delete-btn" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                            🗑️ Delete
                                        </button>
                                    <?php else: ?>
                                        <button class="delete-btn" disabled style="opacity: 0.5; cursor: not-allowed;">🚫 Current</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer>
            <p>&copy; 2025 JosLee Crocs | Admin Panel - User Management</p>
        </footer>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>⚠️ Confirm Delete</h3>
            <p id="deleteMessageText">Are you sure you want to delete this user?</p>
            <p style="color: #dc3545; font-size: 0.85rem; margin-top: 10px;">⚠️ This will also delete all products associated with this user!</p>
            <div class="modal-buttons">
                <button class="confirm-delete" id="confirmDeleteBtn">Yes, Delete</button>
                <button class="cancel-delete" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let deleteUserId = null;
        let deleteUsername = null;

        function confirmDelete(userId, username) {
            deleteUserId = userId;
            deleteUsername = username;
            document.getElementById('deleteMessageText').innerHTML = `Are you sure you want to delete user "<strong>${username}</strong>"?`;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteUserId = null;
            deleteUsername = null;
        }

        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (deleteUserId) {
                window.location.href = `manage_users.php?delete=${deleteUserId}`;
            }
        };

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        };

        // Search functionality
        function searchUsers() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                if (row.querySelector('td') && row.querySelector('td').innerText !== 'No users found') {
                    const username = row.getAttribute('data-username') || '';
                    const email = row.getAttribute('data-email') || '';
                   
                    
                    if (username.includes(searchValue) || email.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>
