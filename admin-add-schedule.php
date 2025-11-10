<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

$message = '';
$messageType = '';
$db = new VisionDriveDatabase();

// Get courses and campuses for dropdowns
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
    $scheduleType = $_POST['schedule_type'] ?? 'one-off';
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
            
            if ($scheduleType === 'one-off') {
                // Create single schedule
                $stmt = $conn->prepare("
                    INSERT INTO training_sessions (course_id, campus_id, session_date, start_time, end_time, max_participants, instructor_name, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$courseId, $campusId, $sessionDate, $startTime, $endTime, $maxParticipants, $instructorName, $status]);
                
                $message = 'Schedule created successfully';
                $messageType = 'success';
            } else {
                // Create recurring schedules
                $startDate = new DateTime($sessionDate);
                $createdCount = 0;
                
                for ($i = 0; $i < $recurrenceCount; $i++) {
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
                
                $message = "Successfully created {$createdCount} recurring schedules";
                $messageType = 'success';
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $message = 'Error creating schedule: ' . $e->getMessage();
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
    <title>Add New Schedule - Vision Drive Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        * {
            font-family: 'Montserrat', sans-serif;
        }
        
        /* Unified form styles (match course edit) */
        .form-container { background:white; border-radius:10px; padding:40px; box-shadow:0 2px 10px rgba(0,0,0,0.1); max-width:800px; margin:0 auto; }
        .form-title { font-size:28px; font-weight:700; color:var(--text-dark); margin-bottom:20px; text-align:center; }
        .form-group label.form-label { font-weight:600; color:var(--text-dark); margin-bottom:8px; display:block; }
        .form-input, .form-select, .form-textarea { width:100%; padding:12px 15px; border:2px solid #e0e0e0; border-radius:8px; background:#fafafa; font-family:'Montserrat',sans-serif; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color: var(--primary-blue); background:white; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; }
        .form-actions, .form-buttons { display:flex; gap:15px; justify-content:center; margin-top:20px; }
        .success-message { background:#e8f5e8; color:var(--success-green); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; }
        .error-message { background:#ffebee; color:var(--danger-red); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; }
        .page-header { background:white; padding:30px 0; margin-bottom:30px; border-bottom:1px solid #e0e0e0; }
        .page-title { font-size:32px; font-weight:700; color:var(--text-dark); }
        .breadcrumb a { color: var(--primary-blue); text-decoration:none; }
        .schedule-type {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .schedule-option {
            flex: 1;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .schedule-option.active {
            border-color: var(--primary-blue);
            background: #f0f8ff;
        }

        .schedule-option h3 {
            margin: 0 0 10px 0;
            color: var(--text-dark);
            font-size: 18px;
        }

        .schedule-option p {
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Add New Schedule</h1>
                <div class="breadcrumb">
                    <a href="admin-dashboard.php">Admin</a> > 
                    <a href="admin-schedules.php">Schedules</a> > Add New Schedule
                </div>
            </div>

            <div class="form-container">
                <?php if ($message): ?>
                    <div class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <h2 class="form-title">Schedule Details</h2>
                <form method="POST" action="">
                    <!-- Schedule Type Selection -->
                    <div class="form-group">
                        <label>Schedule Type</label>
                        <div class="schedule-type">
                            <div class="schedule-option active" onclick="selectScheduleType('one-off')">
                                <input type="radio" name="schedule_type" value="one-off" checked style="display:none;">
                                <h3>📅 One-off Session</h3>
                                <p>Create a single training session</p>
                            </div>
                            <div class="schedule-option" onclick="selectScheduleType('recurring')">
                                <input type="radio" name="schedule_type" value="recurring" style="display:none;">
                                <h3>🔄 Recurring Schedule</h3>
                                <p>Create multiple sessions with a pattern</p>
                            </div>
                        </div>

                        <!-- Recurrence Settings -->
                        <div class="recurrence-settings" id="recurrenceSettings">
                            <h4>Recurrence Settings</h4>
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
                                    <label for="recurrence_count">Number of Sessions</label>
                                    <input class="form-input" type="number" id="recurrence_count" name="recurrence_count" min="1" max="52" value="4">
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
                                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="campus_id" class="form-label">Campus *</label>
                            <select id="campus_id" name="campus_id" required class="form-select">
                                <option value="">Select Campus</option>
                                <?php foreach ($campuses as $campus): ?>
                                    <option value="<?= $campus['id'] ?>"><?= htmlspecialchars($campus['name']) ?> - <?= htmlspecialchars($campus['location']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="session_date" class="form-label">Start Date *</label>
                            <input class="form-input" type="date" id="session_date" name="session_date" required 
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="start_time" class="form-label">Start Time *</label>
                            <input class="form-input" type="time" id="start_time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time" class="form-label">End Time *</label>
                            <input class="form-input" type="time" id="end_time" name="end_time" required>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_participants" class="form-label">Max Participants</label>
                            <input class="form-input" type="number" id="max_participants" name="max_participants" min="1" max="50" value="10">
                        </div>
                        <div class="form-group">
                            <label for="instructor_name" class="form-label">Instructor Name</label>
                            <input class="form-input" type="text" id="instructor_name" name="instructor_name" placeholder="e.g., John Smith">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="scheduled">Scheduled</option>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn-submit">Create Schedule(s)</button>
                        <a href="admin-schedules.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function selectScheduleType(type) {
            // Update radio buttons
            document.querySelectorAll('input[name="schedule_type"]').forEach(radio => {
                radio.checked = radio.value === type;
            });

            // Update visual selection
            document.querySelectorAll('.schedule-option').forEach(option => {
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