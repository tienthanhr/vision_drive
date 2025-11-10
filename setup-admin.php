<?php
// Setup default admin account
require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    
    // Create admin with email: admin@visiondrive.nz, password: admin123
    $adminCreated = $db->createDefaultAdmin('admin@visiondrive.nz', 'admin123');
    
    if ($adminCreated) {
        echo "Default admin created successfully!\n";
        echo "Email: admin@visiondrive.nz\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin already exists or database error\n";
    }
    
    // Create additional admin with simple username
    $adminCreated2 = $db->createDefaultAdmin('admin', 'admin123');
    
    if ($adminCreated2) {
        echo "Simple admin created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>