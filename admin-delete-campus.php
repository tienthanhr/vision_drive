<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

$campusId = $_GET['id'] ?? null;

if (!$campusId) {
    header('Location: admin-campuses.php?error=Invalid campus ID');
    exit();
}

$db = new VisionDriveDatabase();

// Check if campus exists and get its name
try {
    $stmt = $db->getConnection()->prepare("SELECT campus_name FROM campuses WHERE campus_id = ?");
    $stmt->execute([$campusId]);
    $campus = $stmt->fetch();
    
    if (!$campus) {
        header('Location: admin-campuses.php?error=Campus not found');
        exit();
    }
} catch (Exception $e) {
    header('Location: admin-campuses.php?error=Database error');
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Check if campus has associated training sessions
        $stmt = $db->getConnection()->prepare("SELECT COUNT(*) as count FROM training_sessions WHERE campus_id = ?");
        $stmt->execute([$campusId]);
        $sessionCount = $stmt->fetch()['count'];
        
        if ($sessionCount > 0) {
            header('Location: admin-campuses.php?error=Cannot delete campus with existing training sessions');
            exit();
        }
        
        // Delete campus
        $stmt = $db->getConnection()->prepare("DELETE FROM campuses WHERE campus_id = ?");
        $result = $stmt->execute([$campusId]);
        
        if ($result) {
            header('Location: admin-campuses.php?success=Campus deleted successfully');
        } else {
            header('Location: admin-campuses.php?error=Failed to delete campus');
        }
    } catch (Exception $e) {
        header('Location: admin-campuses.php?error=Database error: ' . $e->getMessage());
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Campus - Vision Drive Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Delete Campus</h1>
                <nav class="breadcrumb">
                    <span>Admin</span> > <a href="admin-campuses.php">Campuses</a> > Delete
                </nav>
            </div>

            <div class="page-content">
                <div class="confirmation-container">
                    <div class="confirmation-box">
                        <div class="confirmation-icon">
                            ⚠️
                        </div>
                        
                        <h2>Confirm Deletion</h2>
                        <p>Are you sure you want to delete the campus:</p>
                        <p class="item-name" style="font-weight: 600; color: var(--danger-red); font-size: 18px;"><?= htmlspecialchars($campus['campus_name']) ?>?</p>
                        
                        <div class="warning-note">
                            ⚠️ This action cannot be undone. The campus will be permanently deleted.
                        </div>

                        <form method="POST" class="confirmation-form">
                            <input type="hidden" name="confirm_delete" value="1">
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-danger">
                                    🗑️ Delete Campus
                                </button>
                                <a href="admin-campuses.php" class="btn btn-secondary">
                                    ← Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .confirmation-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
        }

        .confirmation-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .confirmation-icon {
            color: #f59e0b;
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .confirmation-box h2 {
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .item-name {
            font-weight: bold;
            color: #dc2626;
            font-size: 1.2rem;
            margin: 1rem 0;
        }

        .warning-note {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 0.75rem;
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #92400e;
        }

        .confirmation-form {
            margin-top: 2rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</body>
</html>