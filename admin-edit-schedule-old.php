<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

$sessionId = $_GET['id'] ?? null;
$schedule = null;
$message = '';
$messageType = '';

if (!$sessionId) {
    header('Location: admin-schedules.php');
    exit();
}

$db = new VisionDriveDatabase();

// Get schedule data for editing
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
    $message = 'Error loading schedule data';
    $messageType = 'error';
}

// Get courses and campuses for dropdown
$courses = $db->getCourses();
$campuses = $db->getCampuses();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = $_POST['course_id'] ?? '';
    $campusId = $_POST['campus_id'] ?? '';
    $sessionDate = $_POST['session_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $maxParticipants = intval($_POST['max_participants'] ?? 10);
    $instructorName = trim($_POST['instructor_name'] ?? '');
    $status = $_POST['status'] ?? 'scheduled';
    
    if (empty($courseId) || empty($campusId) || empty($sessionDate) || empty($startTime) || empty($endTime)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } elseif ($startTime >= $endTime) {
        $message = 'End time must be after start time';
        $messageType = 'error';
    } else {
        try {
            $stmt = $db->getConnection()->prepare("
                UPDATE training_sessions 
                SET course_id = ?, campus_id = ?, session_date = ?, start_time = ?, end_time = ?, 
                    max_participants = ?, instructor_name = ?, status = ?
                WHERE session_id = ?
            ");
            
            $result = $stmt->execute([
                $courseId,
                $campusId,
                $sessionDate,
                $startTime,
                $endTime,
                $maxParticipants,
                $instructorName ?: null,
                $status,
                $sessionId
            ]);
            
            if ($result) {
                header('Location: admin-schedules.php?success=Schedule updated successfully');
                exit();
            } else {
                $message = 'Failed to update schedule';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule - Vision Drive Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Edit Schedule</h1>
                <nav class="breadcrumb">
                    <span>Admin</span> > <a href="admin-schedules.php">Schedules</a> > Edit
                </nav>
            </header>

            <div class="content-body">
                <?php if ($message): ?>
                    <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" class="admin-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_id">Course *</label>
                                <select id="course_id" name="course_id" required>
                                    <option value="">Select a course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course['id'] ?>" <?= ($schedule['course_id'] ?? '') == $course['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($course['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="campus_id">Campus *</label>
                                <select id="campus_id" name="campus_id" required>
                                    <option value="">Select a campus</option>
                                    <?php foreach ($campuses as $campus): ?>
                                        <option value="<?= $campus['id'] ?>" <?= ($schedule['campus_id'] ?? '') == $campus['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($campus['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="session_date">Session Date *</label>
                            <input type="date" id="session_date" name="session_date" value="<?= $schedule['session_date'] ?? '' ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_time">Start Time *</label>
                                <input type="time" id="start_time" name="start_time" value="<?= $schedule['start_time'] ?? '' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">End Time *</label>
                                <input type="time" id="end_time" name="end_time" value="<?= $schedule['end_time'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_participants">Max Participants *</label>
                                <input type="number" id="max_participants" name="max_participants" value="<?= $schedule['max_participants'] ?? 10 ?>" min="1" max="50" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="instructor_name">Instructor Name</label>
                                <input type="text" id="instructor_name" name="instructor_name" value="<?= htmlspecialchars($schedule['instructor_name'] ?? '') ?>" placeholder="Optional">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="scheduled" <?= ($schedule['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="ongoing" <?= ($schedule['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="completed" <?= ($schedule['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($schedule['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Schedule
                            </button>
                            <a href="admin-schedules.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Schedules
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>