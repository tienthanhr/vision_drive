<?php
session_start();

// Ensure admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$name = $_GET['name'] ?? '';
if ($name === '') {
    header('Location: admin-documents.php?error=' . urlencode('Missing document name'));
    exit();
}

// Sanitize file name and build path
$safeName = basename($name);
$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $safeName;

if (!is_file($path)) {
    header('Location: admin-documents.php?error=' . urlencode('Document not found'));
    exit();
}

// Get document info from database if available
$db = new VisionDriveDatabase();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT * FROM documents WHERE file_name = ? LIMIT 1");
$stmt->execute([$safeName]);
$document = $stmt->fetch();

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: admin-documents.php?error=' . urlencode('Invalid CSRF token'));
        exit();
    }
    
    if (@unlink($path)) {
        // Also delete from database if exists
        if ($document) {
            $stmt = $conn->prepare("DELETE FROM documents WHERE file_name = ?");
            $stmt->execute([$safeName]);
        }
        header('Location: admin-documents.php?success=' . urlencode('Document deleted'));
    } else {
        header('Location: admin-documents.php?error=' . urlencode('Failed to delete document'));
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Document - Vision Drive Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .delete-confirmation {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .delete-confirmation h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .document-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .document-info p {
            margin: 10px 0;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="delete-confirmation">
        <h2>⚠️ Confirm Document Deletion</h2>
        <p>Are you sure you want to delete this document? This action cannot be undone.</p>
        
        <div class="document-info">
            <p><strong>File Name:</strong> <?php echo htmlspecialchars($safeName); ?></p>
            <?php if ($document): ?>
                <p><strong>Document Type:</strong> <?php echo htmlspecialchars($document['document_type'] ?? 'N/A'); ?></p>
                <p><strong>Uploaded:</strong> <?php echo htmlspecialchars($document['upload_date'] ?? 'N/A'); ?></p>
            <?php endif; ?>
            <p><strong>File Size:</strong> <?php echo number_format(filesize($path) / 1024, 2); ?> KB</p>
        </div>
        
        <form method="POST" class="button-group">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($safeName); ?>">
            <button type="submit" name="confirm_delete" class="btn-danger">Yes, Delete Document</button>
            <a href="admin-documents.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
