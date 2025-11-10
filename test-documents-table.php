<?php
// Test script to verify documents table structure

require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    $conn = $db->getConnection();
    
    echo "Testing documents table...\n\n";
    
    // Get table structure
    $stmt = $conn->query("DESCRIBE documents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table structure:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-20s %-20s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key']
        );
    }
    
    echo "\n✅ Table structure retrieved successfully!\n\n";
    
    // Try a test insert
    echo "Testing insert with dummy data...\n";
    
    $testData = [
        'user_id' => 1,
        'document_type' => 'test',
        'original_name' => 'test.pdf',
        'file_path' => 'uploads/documents/test.pdf',
        'file_size' => 1024,
        'mime_type' => 'application/pdf'
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO documents (user_id, document_type, file_name, file_path, file_size, mime_type, uploaded_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $testData['user_id'],
        $testData['document_type'],
        $testData['original_name'],
        $testData['file_path'],
        $testData['file_size'],
        $testData['mime_type']
    ]);
    
    if ($result) {
        $lastId = $conn->lastInsertId();
        echo "✅ Test insert successful! Document ID: $lastId\n";
        
        // Clean up test data
        $stmt = $conn->prepare("DELETE FROM documents WHERE document_id = ?");
        $stmt->execute([$lastId]);
        echo "✅ Test data cleaned up\n";
    } else {
        echo "❌ Test insert failed!\n";
        print_r($stmt->errorInfo());
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
