<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

require_once 'config/database.php';

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit();
}

$db = new VisionDriveDatabase();

try {
    $documents = $db->getStudentDocuments($userId);
    
    // Format dates for display
    foreach ($documents as &$doc) {
        $date = new DateTime($doc['uploaded_at']);
        $doc['uploaded_at'] = $date->format('M j, Y');
    }
    
    echo json_encode([
        'success' => true, 
        'documents' => $documents
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>