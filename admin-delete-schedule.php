<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    header('Location: admin-schedules.php?error=Invalid schedule ID');
    exit();
}

$db = new VisionDriveDatabase();

// Check if schedule exists and get its details
try {
    $stmt = $db->getConnection()->prepare("
        SELECT ts.*, c.course_name, cam.campus_name 
        FROM training_sessions ts
        JOIN courses c ON ts.course_id = c.course_id  
        JOIN campuses cam ON ts.campus_id = cam.campus_id
        WHERE ts.session_id = ?
    ");
    $stmt->execute([$sessionId]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        header('Location: admin-schedules.php?error=Schedule not found');
        exit();
    }
} catch (Exception $e) {
    header('Location: admin-schedules.php?error=Database error');
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Check if schedule has bookings
        $stmt = $db->getConnection()->prepare("SELECT COUNT(*) as count FROM bookings WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $bookingCount = $stmt->fetch()['count'];
        
        if ($bookingCount > 0) {
            header('Location: admin-schedules.php?error=Cannot delete schedule with existing bookings');
            exit();
        }
        
        // Delete schedule
        $stmt = $db->getConnection()->prepare("DELETE FROM training_sessions WHERE session_id = ?");
        $result = $stmt->execute([$sessionId]);
        
        if ($result) {
            header('Location: admin-schedules.php?success=Schedule deleted successfully');
        } else {
            header('Location: admin-schedules.php?error=Failed to delete schedule');
        }
    } catch (Exception $e) {
        header('Location: admin-schedules.php?error=Database error: ' . $e->getMessage());
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Schedule - Vision Drive Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Delete Schedule</h1>
                <nav class="breadcrumb">
                    <span>Admin</span> > <a href="admin-schedules.php">Schedules</a> > Delete
                </nav>
            </div>

            <div class="page-content">
                <div class="confirmation-container">
                    <div class="confirmation-box">
                        <div class="confirmation-icon">
                            ⚠️
                        </div>
                        
                        <h2>Confirm Deletion</h2>
                        <p>Are you sure you want to delete this training schedule?</p>
                        
                        <div class="schedule-details">
                            <h3 style="color: #f44336;"><?= htmlspecialchars($schedule['course_name']) ?></h3>
                            <p><strong>Campus:</strong> <?= htmlspecialchars($schedule['campus_name']) ?></p>
                            <p><strong>Date:</strong> <?= date('M j, Y', strtotime($schedule['session_date'])) ?></p>
                            <p><strong>Time:</strong> <?= date('g:i A', strtotime($schedule['start_time'])) ?> - <?= date('g:i A', strtotime($schedule['end_time'])) ?></p>
                            <p><strong>Participants:</strong> <?= $schedule['current_participants'] ?>/<?= $schedule['max_participants'] ?></p>
                        </div>
                        
                        <div class="warning-note">
                            ⚠️ This action cannot be undone. The schedule will be permanently deleted.
                        </div>

                        <form method="POST" class="confirmation-form">
                            <input type="hidden" name="confirm_delete" value="1">
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-danger">
                                    🗑️ Delete Schedule
                                </button>
                                <a href="admin-schedules.php" class="btn btn-secondary">
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

        .schedule-details {
            background: #f9fafb;
            border-radius: 6px;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
        }

        .schedule-details h3 {
            color: #dc2626;
            margin-bottom: 0.5rem;
        }

        .schedule-details p {
            margin: 0.25rem 0;
            color: #4b5563;
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