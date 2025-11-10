<?php
// Migration script to add file_size and mime_type columns to documents table

require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    $conn = $db->getConnection();
    
    echo "Starting migration...\n";
    
    // Check if columns already exist (MySQL version)
    $stmt = $conn->query("DESCRIBE documents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasFileSize = false;
    $hasMimeType = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'file_size') {
            $hasFileSize = true;
        }
        if ($column['Field'] === 'mime_type') {
            $hasMimeType = true;
        }
    }
    
    // Add missing columns
    if (!$hasFileSize) {
        $conn->exec("ALTER TABLE documents ADD COLUMN file_size INT");
        echo "✓ Added file_size column\n";
    } else {
        echo "✓ file_size column already exists\n";
    }
    
    if (!$hasMimeType) {
        $conn->exec("ALTER TABLE documents ADD COLUMN mime_type VARCHAR(100)");
        echo "✓ Added mime_type column\n";
    } else {
        echo "✓ mime_type column already exists\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
