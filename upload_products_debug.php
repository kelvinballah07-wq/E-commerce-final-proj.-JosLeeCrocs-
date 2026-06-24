<?php
echo "<h2>File Permissions Check</h2>";

// Check current directory
echo "<p>Current directory: " . __DIR__ . "</p>";

// Check if we can write to current directory
if (is_writable(__DIR__)) {
    echo "<p style='color:green'>✅ Current directory is writable</p>";
} else {
    echo "<p style='color:red'>❌ Current directory is NOT writable</p>";
}

// Create a test file
$test_file = __DIR__ . '/test_write.txt';
if (file_put_contents($test_file, 'test')) {
    echo "<p style='color:green'>✅ Can write files: " . $test_file . "</p>";
    unlink($test_file);
} else {
    echo "<p style='color:red'>❌ Cannot write files</p>";
}

// Check uploads directory
$upload_dir = __DIR__ . '/uploads/';
if (file_exists($upload_dir)) {
    echo "<p>Uploads directory exists</p>";
    if (is_writable($upload_dir)) {
        echo "<p style='color:green'>✅ Uploads directory is writable</p>";
    } else {
        echo "<p style='color:red'>❌ Uploads directory is NOT writable</p>";
    }
} else {
    echo "<p>Uploads directory does not exist</p>";
    // Try to create it
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color:green'>✅ Created uploads directory</p>";
    } else {
        echo "<p style='color:red'>❌ Could not create uploads directory</p>";
    }
}

// Check PHP error log
echo "<h2>PHP Error Log</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "<pre>";
    echo htmlspecialchars(file_get_contents($error_log));
    echo "</pre>";
} else {
    echo "<p>No error log found at: $error_log</p>";
}
?>
