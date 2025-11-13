<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

// CSRF token check
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: admin-schedules.php?error=' . urlencode('Invalid CSRF token'));
        exit();
    }
    
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    
    if ($userId <= 0 || $sessionId <= 0) {
        header('Location: admin-schedules.php?error=' . urlencode('Invalid parameters'));
        exit();
    }
    
    try {
        $db = new VisionDriveDatabase();
        $result = $db->removeStudentFromSession($userId, $sessionId);
        
        if ($result['success']) {
            header('Location: admin-view-enrolled-students.php?session_id=' . $sessionId . '&success=' . urlencode($result['message']));
        } else {
            header('Location: admin-view-enrolled-students.php?session_id=' . $sessionId . '&error=' . urlencode($result['message']));
        }
        exit();
        
    } catch (Exception $e) {
        header('Location: admin-view-enrolled-students.php?session_id=' . $sessionId . '&error=' . urlencode('Failed to remove student'));
        exit();
    }
} else {
    // GET request - show confirmation
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
    
    if ($userId <= 0 || $sessionId <= 0) {
        header('Location: admin-schedules.php?error=' . urlencode('Invalid parameters'));
        exit();
    }
    
    try {
        $db = new VisionDriveDatabase();
        $conn = $db->getConnection();
        
        // Get student and session info
        $stmt = $conn->prepare("
            SELECT 
                u.user_id,
                CONCAT(u.first_name, ' ', u.last_name) as student_name,
                c.course_name,
                ts.session_date,
                ca.campus_name
            FROM users u
            CROSS JOIN training_sessions ts
            INNER JOIN courses c ON ts.course_id = c.course_id
            INNER JOIN campuses ca ON ts.campus_id = ca.campus_id
            WHERE u.user_id = ? AND ts.session_id = ?
        ");
        $stmt->execute([$userId, $sessionId]);
        $info = $stmt->fetch();
        
        if (!$info) {
            header('Location: admin-schedules.php?error=' . urlencode('Information not found'));
            exit();
        }
    } catch (Exception $e) {
        header('Location: admin-schedules.php?error=' . urlencode('Database error'));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Student - Vision Drive Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/admin-styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            display: flex;
            font-family: 'Montserrat', sans-serif;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
            width: 100%;
        }
        
        .confirmation-box {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .warning-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 24px;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .confirmation-message {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .confirmation-box {
                margin: 20px auto;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <div class="confirmation-box">
            <div class="warning-icon">⚠️</div>
            <h1 class="confirmation-title">Remove Student from Session?</h1>
            <p class="confirmation-message">
                Are you sure you want to remove this student from the training session? 
                This action will cancel their booking and cannot be undone.
            </p>
            
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($info['student_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Course:</span>
                    <span class="info-value"><?php echo htmlspecialchars($info['course_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Campus:</span>
                    <span class="info-value"><?php echo htmlspecialchars($info['campus_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Session Date:</span>
                    <span class="info-value"><?php echo date('d M Y', strtotime($info['session_date'])); ?></span>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                <input type="hidden" name="session_id" value="<?php echo $sessionId; ?>">
                
                <div class="button-group">
                    <a href="admin-view-enrolled-students.php?session_id=<?php echo $sessionId; ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-danger">
                        Yes, Remove Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
