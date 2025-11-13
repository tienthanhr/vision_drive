<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$courseId = intval($_GET['id'] ?? 0);
if (!$courseId) {
    header('Location: admin-courses.php?error=invalid_id');
    exit();
}

try {
    $db = new VisionDriveDatabase();
    $course = $db->getCourseById($courseId);
    
    if (!$course) {
        header('Location: admin-courses.php?error=course_not_found');
        exit();
    }
    
    // Handle deletion confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        // CSRF validation
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            header('Location: admin-courses.php?error=invalid_csrf');
            exit();
        }
        
        $deleted = $db->deleteCourse($courseId);
        
        if ($deleted) {
            header('Location: admin-courses.php?success=course_deleted');
        } else {
            header('Location: admin-courses.php?error=delete_failed');
        }
        exit();
    }
    
} catch (Exception $e) {
    header('Location: admin-courses.php?error=database_error');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Course - Vision Drive Admin</title>
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
        .course-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .course-info p {
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
        <h2>⚠️ Confirm Course Deletion</h2>
        <p>Are you sure you want to delete this course? This action cannot be undone.</p>
        
        <div class="course-info">
            <p><strong>Course Name:</strong> <?php echo htmlspecialchars($course['name']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($course['description']); ?></p>
            <p><strong>Duration:</strong> <?php echo htmlspecialchars($course['duration']); ?></p>
            <p><strong>Price:</strong> $<?php echo htmlspecialchars($course['price']); ?></p>
        </div>
        
        <form method="POST" class="button-group">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="confirm_delete" class="btn-danger">Yes, Delete Course</button>
            <a href="admin-courses.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>