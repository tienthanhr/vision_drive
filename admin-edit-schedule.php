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
    $maxParticipants = $_POST['max_participants'] ?? '';
    $instructorName = $_POST['instructor_name'] ?? '';
    $status = $_POST['status'] ?? 'scheduled';
    $updateType = $_POST['update_type'] ?? 'single';
    $recurrenceFrequency = $_POST['recurrence_frequency'] ?? '';
    $recurrenceCount = $_POST['recurrence_count'] ?? 1;
    
    if (empty($courseId) || empty($campusId) || empty($sessionDate) || empty($startTime) || empty($endTime)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } elseif ($startTime >= $endTime) {
        $message = 'End time must be after start time';
        $messageType = 'error';
    } else {
        try {
            $conn = $db->getConnection();
            $conn->beginTransaction();
            
            if ($updateType === 'single') {
                // Update single schedule
                $stmt = $conn->prepare("
                    UPDATE training_sessions 
                    SET course_id = ?, campus_id = ?, session_date = ?, start_time = ?, end_time = ?, 
                        max_participants = ?, instructor_name = ?, status = ?
                    WHERE session_id = ?
                ");
                $stmt->execute([$courseId, $campusId, $sessionDate, $startTime, $endTime, $maxParticipants, $instructorName, $status, $sessionId]);
                
                $message = 'Schedule updated successfully';
                $messageType = 'success';
            } else {
                // Create additional recurring schedules from this date
                $startDate = new DateTime($sessionDate);
                $createdCount = 0;
                
                // Update the current schedule first
                $stmt = $conn->prepare("
                    UPDATE training_sessions 
                    SET course_id = ?, campus_id = ?, session_date = ?, start_time = ?, end_time = ?, 
                        max_participants = ?, instructor_name = ?, status = ?
                    WHERE session_id = ?
                ");
                $stmt->execute([$courseId, $campusId, $sessionDate, $startTime, $endTime, $maxParticipants, $instructorName, $status, $sessionId]);
                
                // Create additional sessions (starting from next occurrence)
                for ($i = 1; $i < $recurrenceCount; $i++) {
                    $currentDate = clone $startDate;
                    
                    // Calculate next date based on frequency
                    switch ($recurrenceFrequency) {
                        case 'daily':
                            $currentDate->add(new DateInterval('P' . $i . 'D'));
                            break;
                        case 'weekly':
                            $currentDate->add(new DateInterval('P' . ($i * 7) . 'D'));
                            break;
                        case 'monthly':
                            $currentDate->add(new DateInterval('P' . $i . 'M'));
                            break;
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO training_sessions (course_id, campus_id, session_date, start_time, end_time, max_participants, instructor_name, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$courseId, $campusId, $currentDate->format('Y-m-d'), $startTime, $endTime, $maxParticipants, $instructorName, $status]);
                    $createdCount++;
                }
                
                $message = "Schedule updated and {$createdCount} additional recurring schedules created";
                $messageType = 'success';
            }
            
            $conn->commit();
            
            // Refresh schedule data
            $stmt = $db->getConnection()->prepare("
                SELECT ts.*, c.course_name, cam.campus_name 
                FROM training_sessions ts
                JOIN courses c ON ts.course_id = c.course_id  
                JOIN campuses cam ON ts.campus_id = cam.campus_id
                WHERE ts.session_id = ?
            ");
            $stmt->execute([$sessionId]);
            $schedule = $stmt->fetch();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $message = 'Error updating schedule: ' . $e->getMessage();
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
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        /* Unified form styles (match course edit) */
        .form-container { background:white; border-radius:10px; padding:40px; box-shadow:0 2px 10px rgba(0,0,0,0.1); max-width:800px; margin:0 auto; }
        .form-title { font-size:28px; font-weight:700; color:var(--text-dark); margin-bottom:20px; text-align:center; }
        .form-group label.form-label { font-weight:600; color:var(--text-dark); margin-bottom:8px; display:block; }
        .form-input, .form-select, .form-textarea { width:100%; padding:12px 15px; border:2px solid #e0e0e0; border-radius:8px; background:#fafafa; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color: var(--primary-blue); background:white; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; }
        .form-actions, .form-buttons { display:flex; gap:15px; justify-content:center; margin-top:20px; }
        .success-message { background:#e8f5e8; color:var(--success-green); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; }
        .error-message { background:#ffebee; color:var(--danger-red); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; }
        .page-header { background:white; padding:30px 0; margin-bottom:30px; border-bottom:1px solid #e0e0e0; }
        .page-title { font-size:32px; font-weight:700; color:var(--text-dark); }
        .breadcrumb a { color: var(--primary-blue); text-decoration:none; }
        .update-type {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .update-option {
            flex: 1;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .update-option.active {
            border-color: var(--primary-blue);
            background: #f0f8ff;
        }

        .update-option h3 {
            margin: 0 0 10px 0;
            color: var(--text-dark);
            font-size: 18px;
        }

        .update-option p {
            margin: 0;
            color: var(--text-light);
            font-size: 14px;
        }

        .recurrence-settings {
            display: none;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 15px;
        }

        .recurrence-settings.show {
            display: block;
        }

        .frequency-options {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .frequency-btn {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .frequency-btn.active {
            border-color: var(--primary-blue);
            background: var(--primary-blue);
            color: white;
        }

        @media (max-width: 768px){ .form-row, .form-row-3{ grid-template-columns:1fr; } .form-container{ padding:30px 20px; margin:20px; } }

        .schedule-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .schedule-info h4 {
            margin: 0 0 10px 0;
            color: var(--primary-blue);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Edit Schedule</h1>
                <div class="breadcrumb">
                    <a href="admin-dashboard.php">Admin</a> > 
                    <a href="admin-schedules.php">Schedules</a> > Edit Schedule
                </div>
            </div>

            <div class="form-container">
                <?php if ($message): ?>
                    <div class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($schedule): ?>
                    <!-- Current Schedule Info -->
                    <div class="schedule-info">
                        <h4>📋 Current Schedule Information</h4>
                        <p><strong>Course:</strong> <?= htmlspecialchars($schedule['course_name']) ?></p>
                        <p><strong>Campus:</strong> <?= htmlspecialchars($schedule['campus_name']) ?></p>
                        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($schedule['session_date'])) ?></p>
                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($schedule['start_time'])) ?> - <?= date('g:i A', strtotime($schedule['end_time'])) ?></p>
                    </div>

                    <h2 class="form-title">Update Schedule</h2>
                    <form method="POST" action="">
                        <!-- Update Type Selection -->
                        <div class="form-group">
                            <label>Update Options</label>
                            <div class="update-type">
                                <div class="update-option active" onclick="selectUpdateType('single')">
                                    <input type="radio" name="update_type" value="single" checked style="display:none;">
                                    <h3>📝 Update This Session Only</h3>
                                    <p>Modify just this training session</p>
                                </div>
                                <div class="update-option" onclick="selectUpdateType('recurring')">
                                    <input type="radio" name="update_type" value="recurring" style="display:none;">
                                    <h3>🔄 Update + Add Recurring</h3>
                                    <p>Update this session and create additional recurring sessions</p>
                                </div>
                            </div>

                            <!-- Recurrence Settings -->
                            <div class="recurrence-settings" id="recurrenceSettings">
                                <h4>Create Additional Sessions</h4>
                                <div class="form-row">
                                    <div>
                                        <label>Frequency</label>
                                        <div class="frequency-options">
                                            <button type="button" class="frequency-btn active" onclick="selectFrequency('daily')">Daily</button>
                                            <button type="button" class="frequency-btn" onclick="selectFrequency('weekly')">Weekly</button>
                                            <button type="button" class="frequency-btn" onclick="selectFrequency('monthly')">Monthly</button>
                                        </div>
                                        <input type="hidden" name="recurrence_frequency" value="daily" id="frequencyInput">
                                    </div>
                                    <div>
                                        <label for="recurrence_count">Total Sessions (including this one)</label>
                                        <input type="number" id="recurrence_count" name="recurrence_count" min="1" max="52" value="4">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_id" class="form-label">Course *</label>
                                <select id="course_id" name="course_id" required class="form-select">
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course['id'] ?>" 
                                                <?= $course['id'] == $schedule['course_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($course['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="campus_id" class="form-label">Campus *</label>
                                <select id="campus_id" name="campus_id" required class="form-select">
                                    <option value="">Select Campus</option>
                                    <?php foreach ($campuses as $campus): ?>
                                        <option value="<?= $campus['id'] ?>" 
                                                <?= $campus['id'] == $schedule['campus_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($campus['name']) ?> - <?= htmlspecialchars($campus['location']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Date and Time -->
                        <div class="form-row-3">
                            <div class="form-group">
                                <label for="session_date" class="form-label">Session Date *</label>
                                <input class="form-input" type="date" id="session_date" name="session_date" required 
                                       value="<?= $schedule['session_date'] ?>">
                            </div>
                            <div class="form-group">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input class="form-input" type="time" id="start_time" name="start_time" required
                                       value="<?= $schedule['start_time'] ?>">
                            </div>
                            <div class="form-group">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input class="form-input" type="time" id="end_time" name="end_time" required
                                       value="<?= $schedule['end_time'] ?>">
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_participants" class="form-label">Max Participants</label>
                                <input class="form-input" type="number" id="max_participants" name="max_participants" min="1" max="50" 
                                       value="<?= $schedule['max_participants'] ?>">
                            </div>
                            <div class="form-group">
                                <label for="instructor_name" class="form-label">Instructor Name</label>
                                <input class="form-input" type="text" id="instructor_name" name="instructor_name" 
                                       value="<?= htmlspecialchars($schedule['instructor_name']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="scheduled" <?= $schedule['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="active" <?= $schedule['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="completed" <?= $schedule['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $schedule['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" class="btn-submit">Update Schedule</button>
                            <a href="admin-schedules.php" class="btn-cancel">Cancel</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">Schedule not found or could not be loaded.</div>
                    <a href="admin-schedules.php" class="btn btn-secondary">Back to Schedules</a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function selectUpdateType(type) {
            // Update radio buttons
            document.querySelectorAll('input[name="update_type"]').forEach(radio => {
                radio.checked = radio.value === type;
            });

            // Update visual selection
            document.querySelectorAll('.update-option').forEach(option => {
                option.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Show/hide recurrence settings
            const recurrenceSettings = document.getElementById('recurrenceSettings');
            if (type === 'recurring') {
                recurrenceSettings.classList.add('show');
            } else {
                recurrenceSettings.classList.remove('show');
            }
        }

        function selectFrequency(frequency) {
            // Update hidden input
            document.getElementById('frequencyInput').value = frequency;

            // Update visual selection
            document.querySelectorAll('.frequency-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Update suggested count based on frequency
            const countInput = document.getElementById('recurrence_count');
            if (frequency === 'daily') {
                countInput.value = 5; // 5 days
            } else if (frequency === 'weekly') {
                countInput.value = 4; // 4 weeks
            } else if (frequency === 'monthly') {
                countInput.value = 3; // 3 months
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>