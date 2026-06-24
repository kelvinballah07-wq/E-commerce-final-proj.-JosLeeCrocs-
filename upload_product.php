<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'Connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle product upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $product_description = trim($_POST['product_description']);
    $product_type = trim($_POST['product_type']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $image_url = 'https://placehold.co/400x300?text=No+Image';
    
    if (empty($product_name) || empty($product_description) || $price <= 0 || $quantity <= 0) {
        $error = 'Please fill in all fields with valid values.';
    } else {
        // Handle image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK && $_FILES['product_image']['size'] > 0) {
            $upload_dir = 'uploads/';
            
            // Create uploads directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $filename = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Validate image type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                } else {
                    $error = 'Failed to upload image.';
                }
            } else {
                $error = 'Only JPG, JPEG, PNG, GIF & WEBP files are allowed.';
            }
        }
        
        if (empty($error)) {
            $sql = "INSERT INTO products (user_id, product_name, product_description, product_type, price, quantity, image_url, date_ordered, status, created_at) 
                    VALUES ('$user_id', '$product_name', '$product_description', '$product_type', '$price', '$quantity', '$image_url', CURDATE(), 'pending_payment', NOW())";
            
            if ($conn->query($sql)) {
                $message = 'Product uploaded successfully!';
                // Clear form data by resetting POST
                $_POST = array();
            } else {
                $error = 'Database Error: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Product - JosLee Crocs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #FEF7E8 0%, #FDF2E3 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 48px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(85, 55, 35, 0.1);
        }
        h1 { 
            text-align: center; 
            color: #3E3A35; 
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #7A5A4B; 
            font-weight: 600; 
        }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%; 
            padding: 12px 16px; 
            border: 2px solid #F0E0CE; 
            border-radius: 60px; 
            font-size: 16px;
            background: #FFFDF9;
            transition: all 0.2s ease;
        }
        input[type="text"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #E8B86B;
            box-shadow: 0 0 0 3px rgba(232, 184, 107, 0.2);
        }
        input[type="file"] {
            width: 100%; 
            padding: 12px; 
            border: 2px solid #F0E0CE; 
            border-radius: 60px; 
            font-size: 16px;
            background: #FFFDF9;
        }
        textarea { 
            resize: vertical; 
            min-height: 100px;
            border-radius: 24px;
        }
        .btn {
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(115deg, #D97A5C 0%, #C27046 100%);
            color: white; 
            border: none; 
            border-radius: 60px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn:hover { 
            background: linear-gradient(115deg, #C76846 0%, #B55A36 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(217, 122, 92, 0.3);
        }
        .btn-secondary { 
            background: #EFE2D4; 
            color: #3E3A35;
            margin-top: 10px; 
            text-decoration: none; 
            display: inline-block; 
            text-align: center;
        }
        .btn-secondary:hover {
            background: #E5D5C4;
            transform: translateY(-2px);
            box-shadow: none;
        }
        .message { 
            padding: 14px 20px; 
            border-radius: 60px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-weight: 500;
        }
        .success { 
            background: #ECF7E6; 
            color: #5A7A3A; 
            border-left: 5px solid #7A8B5E; 
        }
        .error { 
            background: #FDF2F0; 
            color: #B85C3A; 
            border-left: 5px solid #D97A5C; 
        }
        .image-preview {
            margin-top: 10px;
            text-align: center;
            display: none;
        }
        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 24px;
            border: 2px solid #F0E0CE;
        }
        .image-preview.show {
            display: block;
        }
        /* Auto-hide message after 3 seconds */
        .message {
            animation: fadeOut 3s ease forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23D97A5C' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Upload Your Crochet Product</h1>
        
        <?php if ($message): ?>
            <div class="message success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>🧶 Product Name *</label>
                <input type="text" name="product_name" value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>🏷️ Product Type *</label>
                <select name="product_type" required>
                    <option value="Knitted Item" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Knitted Item') ? 'selected' : ''; ?>>Knitted Item</option>
                    <option value="Sweater" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Sweater') ? 'selected' : ''; ?>>Sweater</option>
                    <option value="Hat" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Hat') ? 'selected' : ''; ?>>Hat</option>
                    <option value="Blanket" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Blanket') ? 'selected' : ''; ?>>Blanket</option>
                    <option value="Accessory" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Accessory') ? 'selected' : ''; ?>>Accessory</option>
                    <option value="Home Decor" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Home Decor') ? 'selected' : ''; ?>>Home Decor</option>
                    <option value="Crochet" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Crochet') ? 'selected' : ''; ?>>Crochet</option>
                    <option value="Other" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>📝 Product Description *</label>
                <textarea name="product_description" required placeholder="Describe your crochet product..."><?php echo isset($_POST['product_description']) ? htmlspecialchars($_POST['product_description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>💰 Price (RWF) *</label>
                <input type="number" name="price" step="0.01" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>📦 Quantity *</label>
                <input type="number" name="quantity" value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '1'; ?>" min="1" required>
            </div>
            
            <div class="form-group">
                <label>🖼️ Product Image</label>
                <input type="file" name="product_image" accept="image/*" onchange="previewImage(this)">
                <div class="image-preview" id="imagePreview">
                    <img id="previewImg" src="" alt="Preview">
                </div>
            </div>
            
            <button type="submit" class="btn">✨ Upload Product</button>
            <a href="view_store.php" class="btn btn-secondary" style="display: inline-block; text-align: center; text-decoration: none;">← Back to Store</a>
        </form>
    </div>
    
    <script>
        function previewImage(input) {
            const previewDiv = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewDiv.classList.add('show');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                previewDiv.classList.remove('show');
                previewImg.src = '';
            }
        }
        
        // Clear form after successful upload via JavaScript
        <?php if ($message && empty($error)): ?>
        setTimeout(function() {
            // Clear all form fields
            document.querySelector('form').reset();
            // Clear image preview
            document.getElementById('imagePreview').classList.remove('show');
            document.getElementById('previewImg').src = '';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
