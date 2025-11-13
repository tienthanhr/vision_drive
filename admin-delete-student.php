<?php
session_start();

// Admin auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once __DIR__ . '/config/database.php';

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin-students.php?error=' . urlencode('Invalid student id'));
    exit();
}

try {
    $db = new VisionDriveDatabase();
    $conn = $db->getConnection();
    
    // Get student info
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, phone FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: admin-students.php?error=' . urlencode('Student not found'));
        exit();
    }
    
    // Handle deletion confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        // CSRF validation
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            header('Location: admin-students.php?error=' . urlencode('Invalid CSRF token'));
            exit();
        }
        
        // Hard delete - permanently remove student
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $ok = $stmt->execute([$id]);
        
        if ($ok && $stmt->rowCount() > 0) {
            header('Location: admin-students.php?success=' . urlencode('Student deleted successfully'));
        } else {
            header('Location: admin-students.php?error=' . urlencode('Failed to delete student'));
        }
        exit();
    }
    
} catch (Exception $e) {
    header('Location: admin-students.php?error=' . urlencode('Delete failed: ' . $e->getMessage()));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Student - Vision Drive Admin</title>
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
        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .student-info p {
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
        <h2>⚠️ Confirm Student Deletion</h2>
        <p>Are you sure you want to delete this student? This action cannot be undone and will remove all associated records.</p>
        
        <div class="student-info">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></p>
        </div>
        
        <form method="POST" class="button-group">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="confirm_delete" class="btn-danger">Yes, Delete Student</button>
            <a href="admin-students.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
