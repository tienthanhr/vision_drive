<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit('Access denied');
}

require_once 'config/database.php';

$documentId = $_GET['id'] ?? null;

if (!$documentId) {
    http_response_code(400);
    exit('Document ID required');
}

$db = new VisionDriveDatabase();

try {
    // Get document info
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("
        SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) as student_name
        FROM documents d
        JOIN users u ON d.user_id = u.user_id  
        WHERE d.document_id = ?
    ");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch();
    
    if (!$document) {
        http_response_code(404);
        exit('Document not found');
    }
    
    $filePath = $document['file_path'];
    
    // Check if file exists
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File not found on disk');
    }
    
    // Set headers for download
    $originalName = $document['file_name'];
    $fileSize = filesize($filePath);
    $mimeType = $document['mime_type'] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($originalName) . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($filePath);
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Download error: ' . $e->getMessage());
}
?>