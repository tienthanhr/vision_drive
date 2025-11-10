<?php
// Simple booking handler
require_once 'config/database.php';

// Start session only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$selectedCourse = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$message = '';
$error = '';

// Get data from database
try {
    $db = new VisionDriveDatabase();
    $courses = $db->getCourses();
    $campuses = $db->getCampuses();
} catch (Exception $e) {
    // Fallback data if database error
    $courses = [
        ['id' => 1, 'name' => 'Forklift Operator', 'description' => 'Basic forklift training', 'duration' => '8 hours', 'price' => 350],
        ['id' => 2, 'name' => 'Forklift Refresher', 'description' => 'Refresher course', 'duration' => '4 hours', 'price' => 180],
        ['id' => 3, 'name' => 'Class 2 Truck', 'description' => 'Heavy vehicle training', 'duration' => '16 hours', 'price' => 750]
    ];
    
    $campuses = [
        ['id' => 1, 'name' => 'Auckland', 'location' => 'Auckland, New Zealand'],
        ['id' => 2, 'name' => 'Hamilton', 'location' => 'Hamilton, New Zealand'],  
        ['id' => 3, 'name' => 'Christchurch', 'location' => 'Christchurch, New Zealand']
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['step']) && $_POST['step'] == '1') {
        // Step 1: Select schedule (includes date, course, campus)
        $selectedSchedule = (int)$_POST['schedule_id'];
        $selectedCourse = (int)$_POST['course_id'];
        $selectedCampus = (int)$_POST['campus_id'];
        $selectedDate = $_POST['selected_date'] ?? '';
        
        if ($selectedSchedule && $selectedCourse && $selectedCampus && $selectedDate) {
            $step = 2;
            $_SESSION['booking_schedule'] = $selectedSchedule;
            $_SESSION['booking_course'] = $selectedCourse;
            $_SESSION['booking_campus'] = $selectedCampus;
            $_SESSION['booking_date'] = $selectedDate;
        } else {
            $error = 'Please select a training session';
        }
        
    } elseif (isset($_POST['step']) && $_POST['step'] == '2') {
        // Step 2: Fill personal info and submit
        
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $courseId = $_SESSION['booking_course'];
        $campusId = $_SESSION['booking_campus'];
        $scheduleId = $_SESSION['booking_schedule'];
        $bookingDate = $_SESSION['booking_date'];
        
        if (empty($name) || empty($email) || empty($phone)) {
            $error = 'Please fill all required fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Handle file uploads
            $uploadedFiles = [];
            if (!empty($_FILES['documents']['name'][0])) {
                $uploadDir = 'uploads/documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                foreach ($_FILES['documents']['name'] as $key => $filename) {
                    if ($_FILES['documents']['error'][$key] == UPLOAD_ERR_OK) {
                        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                        
                        if (in_array($fileExt, $allowedExts)) {
                            $newFilename = time() . '_' . $key . '.' . $fileExt;
                            $uploadPath = $uploadDir . $newFilename;
                            
                            if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $uploadPath)) {
                                $documentType = $_POST['document_type'][$key] ?? 'other';
                                $uploadedFiles[] = [
                                    'original_name' => $filename,
                                    'filename' => $newFilename,
                                    'path' => $uploadPath,
                                    'document_type' => $documentType
                                ];
                            }
                        }
                    }
                }
            }
            
            // Save booking to database
            try {
                $bookingData = [
                    'student_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'course_id' => $courseId,
                    'campus_id' => $campusId,
                    'schedule_id' => $scheduleId,
                    'special_requirements' => $_POST['requirements'] ?? ''
                ];
                
                $result = $db->createBookingWithSchedule($bookingData);
                
                if ($result && isset($result['booking_id'])) {
                    $bookingId = $result['booking_id'];
                    $userId = $result['user_id'];
                    
                    // Save file info to database
                    foreach ($uploadedFiles as $file) {
                        $db->saveDocument([
                            'user_id' => $userId,
                            'document_type' => $file['document_type'],
                            'original_name' => $file['original_name'],
                            'file_path' => $file['path']
                        ]);
                    }
                    
                    $step = 3;
                    $confirmationCode = 'VD' . str_pad($bookingId, 4, '0', STR_PAD_LEFT);
                    $_SESSION['booking_confirmation'] = $confirmationCode;
                    $_SESSION['booking_name'] = $name;
                    $_SESSION['booking_email'] = $email;
                } else {
                    $error = 'Unable to create booking. Please try again.';
                }
                
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Helper function to find course/campus by ID
function findById($array, $id) {
    foreach ($array as $item) {
        if ($item['id'] == $id) {
            return $item;
        }
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Book Training</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/user-styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }

        :root {
            --primary-blue: #00bcd4;
            --secondary-blue: #0097a7;
            --accent-yellow: #ffc107;
            --text-dark: #2c3e50;
            --text-light: #6c757d;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 100;
        }

        .logo {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .logo-text {
            font-size: 36px;
            font-weight: 800;
            color: var(--primary-blue);
            line-height: 1;
            letter-spacing: -2px;
        }

        .logo-subtitle {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-light);
            margin-top: -5px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .back-btn {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--accent-yellow);
            color: var(--text-dark);
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #e0a800;
            transform: translateY(-50%) scale(1.05);
        }

        /* Progress Bar */
        .progress-container {
            background: var(--primary-blue);
            padding: 20px 40px;
        }

        .progress-bar {
            background: rgba(255,255,255,0.3);
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }

        .progress-fill {
            background: white;
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 10px;
        }

        /* Main Content */
        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .step-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .step-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .step-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .step-subtitle {
            color: var(--text-light);
            font-size: 16px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .required {
            color: #e53e3e;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        .form-select {
            background: white;
            cursor: pointer;
        }

        /* Buttons */
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-blue);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: var(--text-dark);
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        /* Success Page */
        .success-icon {
            width: 80px;
            height: 80px;
            background: #4caf50;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }

        .booking-summary {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-label {
            font-weight: 600;
        }

        .confirmation-code {
            color: #e53e3e;
            font-weight: 600;
        }

        .confirmation-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary-blue);
            text-decoration: none;
        }

        /* Footer */
        .footer {
            background: var(--primary-blue);
            color: white;
            padding: 20px 40px;
            margin-top: 40px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            font-size: 18px;
            font-weight: 700;
        }

        .footer-info {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .footer-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        /* Schedule Selection Styles */
        .calendar-container {
            margin: 30px 0;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .calendar-month-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .calendar-nav-btn {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .calendar-nav-btn:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 20px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            color: var(--text-dark);
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
        }

        .calendar-day {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            min-height: 120px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }

        .calendar-day:hover:not(.disabled):not(.past) {
            border-color: var(--primary-blue);
            box-shadow: 0 2px 8px rgba(0, 188, 212, 0.15);
        }

        .calendar-day.disabled {
            background: #f5f5f5;
            opacity: 0.5;
        }

        .calendar-day.past {
            background: #fafafa;
            opacity: 0.6;
        }

        .day-number {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .calendar-day.past .day-number {
            color: #999;
        }

        .day-courses {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            overflow-y: auto;
            max-height: 200px;
        }

        .course-item {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid #2196f3;
            border-radius: 6px;
            padding: 6px 8px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            position: relative;
            font-size: 11px;
            line-height: 1.4;
        }

        .course-item:hover {
            background: linear-gradient(135deg, #bbdefb, #90caf9);
            border-color: #1976d2;
        }

        .course-item.selected {
            background: linear-gradient(135deg, #4caf50, #66bb6a);
            border-color: #2e7d32;
            color: white;
        }

        .course-item.selected::before {
            content: '✓';
            position: absolute;
            top: 4px;
            right: 4px;
            width: 16px;
            height: 16px;
            background: white;
            color: #4caf50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 11px;
        }

        .course-name {
            font-weight: 600;
            margin-bottom: 2px;
            font-size: 11px;
        }

        .course-location {
            font-size: 10px;
            opacity: 0.9;
            margin-bottom: 2px;
        }

        .course-spots {
            font-size: 10px;
            opacity: 0.9;
        }

        .course-details div {
            margin-bottom: 2px;
        }

        .no-courses {
            font-size: 10px;
            color: #999;
            text-align: center;
            padding: 10px 5px;
            font-style: italic;
        }

        .schedule-selection {
            margin: 30px 0;
        }

        .filter-section {
            margin: 30px 0;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
        }

        .filter-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .filter-controls-inline {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .selected-date-display {
            margin-bottom: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-left: 4px solid var(--primary-blue);
            border-radius: 5px;
        }

        .selected-date-display h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .selection-info {
            margin: 20px 0;
            text-align: center;
        }

        .info-message {
            background: #e3f2fd;
            padding: 15px 20px;
            border-radius: 8px;
            color: #1565c0;
            font-size: 14px;
            border-left: 4px solid var(--primary-blue);
        }

        .filter-controls {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .loading-message {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
            font-style: italic;
        }

        .schedule-results {
            display: grid;
            gap: 15px;
        }

        .schedule-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .schedule-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(0, 188, 212, 0.15);
        }

        .schedule-card.selected {
            border-color: var(--primary-blue);
            background: #f0fbff;
            box-shadow: 0 4px 12px rgba(0, 188, 212, 0.2);
        }

        .schedule-card.selected::after {
            content: '✓';
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            background: var(--primary-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .schedule-date {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .schedule-time {
            font-size: 16px;
            color: var(--primary-blue);
            font-weight: 600;
        }

        .schedule-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .schedule-detail {
            font-size: 14px;
            color: var(--text-light);
        }

        .schedule-detail strong {
            color: var(--text-dark);
            font-weight: 600;
        }

        .schedule-capacity {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            margin-top: 10px;
        }

        .schedule-capacity.low {
            background: #fff3e0;
            color: #f57c00;
        }

        .schedule-capacity.full {
            background: #ffebee;
            color: #d32f2f;
        }

        .no-schedules {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .no-schedules-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .progress-container {
                padding: 20px;
            }

            .main-content {
                padding: 20px 10px;
            }

            .step-container {
                padding: 30px 20px;
            }

            .filter-controls {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .schedule-details {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .footer-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .footer-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Progress Bar -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= ($step / 3) * 100 ?>%;"></div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($step == 1): ?>
            <!-- Step 1: Choose Date and Schedule -->
            <div class="step-container">
                <div class="step-header">
                    <h2 class="step-title">Choose Your Training Schedule</h2>
                    <p class="step-subtitle">Step 1 of 3</p>
                </div>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Calendar -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button type="button" id="prevMonth" class="calendar-nav-btn">← Previous</button>
                        <h3 id="currentMonth" class="calendar-month-title"></h3>
                        <button type="button" id="nextMonth" class="calendar-nav-btn">Next →</button>
                    </div>
                    <div id="calendar" class="calendar-grid"></div>
                </div>

                <!-- Filter Controls -->
                <div class="filter-section">
                    <h3>Filter by:</h3>
                    <div class="filter-controls-inline">
                        <div class="form-group">
                            <label class="form-label">Course Type</label>
                            <select id="filterCourse" class="form-select">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <select id="filterCampus" class="form-select">
                                <option value="">All Locations</option>
                                <?php foreach ($campuses as $campus): ?>
                                    <option value="<?= $campus['id'] ?>"><?= htmlspecialchars($campus['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" id="applyFilters" class="btn btn-secondary">Apply Filters</button>
                    </div>
                </div>

                <!-- Available Sessions -->
                <div class="schedule-selection">
                    <div class="selection-info">
                        <p id="selectionMessage" class="info-message">
                            👆 Select a training session from the calendar above by clicking on it. Selected sessions will show a green checkmark.
                        </p>
                    </div>
                </div>

                <form method="POST" id="scheduleFormStep1">
                    <input type="hidden" name="step" value="1">
                    <input type="hidden" name="course_id" id="selectedCourseId" value="">
                    <input type="hidden" name="campus_id" id="selectedCampusId" value="">
                    <input type="hidden" name="schedule_id" id="selectedScheduleId" value="">
                    <input type="hidden" name="selected_date" id="selectedDate" value="">
                    
                    <div class="btn-container">
                        <div></div>
                        <button type="submit" class="btn btn-primary" id="nextStepBtn" disabled>Continue to Personal Details</button>
                    </div>
                </form>
            </div>

        <?php elseif ($step == 2): ?>
            <!-- Step 2: Personal Details -->
            <?php 
            $selectedCourseData = findById($courses, $_SESSION['booking_course'] ?? 0);
            $selectedCampusData = findById($campuses, $_SESSION['booking_campus'] ?? 0);
            $selectedDate = $_SESSION['booking_date'] ?? date('Y-m-d');
            ?>
            <div class="step-container">
                <div class="step-header">
                    <h2 class="step-title">Your Details</h2>
                    <p class="step-subtitle">Step 2 of 3</p>
                </div>

                <div class="booking-summary">
                    <h3>Booking Summary:</h3>
                    <p><strong>Date:</strong> <?= date('l, F j, Y', strtotime($selectedDate)) ?></p>
                    <p><strong>Course:</strong> <?= htmlspecialchars($selectedCourseData['name'] ?? 'Unknown') ?></p>
                    <p><strong>Campus:</strong> <?= htmlspecialchars($selectedCampusData['name'] ?? 'Unknown') ?></p>
                    <p><strong>Price:</strong> $<?= number_format($selectedCourseData['price'] ?? 0, 2) ?></p>
                </div>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-input" placeholder="name@example.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone <span class="required">*</span></label>
                        <input type="tel" name="phone" class="form-input" placeholder="021 123 456" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Upload Documents</label>
                        
                        <div style="margin-bottom: 15px;">
                            <label class="form-label" style="font-weight: 500; margin-bottom: 5px;">Document Type</label>
                            <select name="document_type[]" class="form-select">
                                <option value="id_card">ID Card</option>
                                <option value="license">License</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <input type="file" name="documents[]" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small style="color: #6c757d; font-size: 14px;">Upload ID, license, or other required documents (PDF, JPG, PNG, DOC)</small>
                        
                        <div id="additionalDocs"></div>
                        <button type="button" onclick="addDocumentField()" style="margin-top: 10px; padding: 8px 16px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">+ Add Another Document</button>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Special Requirements</label>
                        <textarea name="requirements" class="form-input" rows="3" placeholder="Any special requirements? (optional)"></textarea>
                    </div>

                    <div class="btn-container">
                        <a href="booking.php?step=1" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Submit Application</button>
                    </div>
                </form>
            </div>

        <?php elseif ($step == 3): ?>
            <!-- Step 3: Success -->
            <div class="step-container">
                <div class="success-icon">✓</div>
                <div class="step-header">
                    <h2 class="step-title">Thank You!</h2>
                    <p class="step-subtitle">Your application has been submitted successfully.<br>We'll be in touch shortly.</p>
                </div>

                <div class="confirmation-details">
                    <h3>Booking Summary</h3>
                    <div class="detail-row">
                        <span class="detail-label">Confirmation Code:</span>
                        <span style="color: #e74c3c; font-weight: bold;"><?= $_SESSION['booking_confirmation'] ?? 'VD0001' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span><?= htmlspecialchars($_SESSION['booking_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span><?= htmlspecialchars($_SESSION['booking_email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span><?= date('d/m/Y H:i') ?></span>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <p><strong>Confirmation email sent!</strong></p>
                    <p>We've sent a confirmation email to <?= htmlspecialchars($_SESSION['booking_email'] ?? 'your email') ?></p>
                    <p>Your login details are included in the email</p>
                </div>

                <div class="back-link">
                    <a href="index.php">Back to Home</a> | 
                    <a href="booking.php">Book Another Course</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Calendar with inline courses
    let selectedScheduleId = null;
    let currentMonth = new Date();
    let allSchedules = []; // Store all schedules
    let filteredSchedules = []; // Store filtered schedules
    let currentFilters = { course: '', campus: '' };
    
    // Load calendar when page loads (for step 1)
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($step == 1): ?>
        // Initialize calendar for step 1
        loadAllSchedules();
        
        document.getElementById('prevMonth').addEventListener('click', function() {
            currentMonth.setMonth(currentMonth.getMonth() - 1);
            renderCalendar();
        });
        
        document.getElementById('nextMonth').addEventListener('click', function() {
            currentMonth.setMonth(currentMonth.getMonth() + 1);
            renderCalendar();
        });
        
        // Filter events
        document.getElementById('applyFilters').addEventListener('click', function() {
            currentFilters.course = document.getElementById('filterCourse').value;
            currentFilters.campus = document.getElementById('filterCampus').value;
            applyFilters();
            renderCalendar();
        });
        <?php endif; ?>
    });
    
    function loadAllSchedules() {
        // Fetch all available training sessions from server
        const startDate = new Date();
        const endDate = new Date();
        endDate.setMonth(endDate.getMonth() + 3);
        
        fetch(`get-available-schedules.php?start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.schedules) {
                    allSchedules = data.schedules;
                    filteredSchedules = [...allSchedules];
                    renderCalendar();
                }
            })
            .catch(error => console.error('Error loading schedules:', error));
    }
    
    function applyFilters() {
        filteredSchedules = allSchedules.filter(schedule => {
            let matches = true;
            
            if (currentFilters.course && schedule.course_id != currentFilters.course) {
                matches = false;
            }
            
            if (currentFilters.campus && schedule.campus_id != currentFilters.campus) {
                matches = false;
            }
            
            return matches;
        });
    }
    
    function renderCalendar() {
        const calendar = document.getElementById('calendar');
        const monthTitle = document.getElementById('currentMonth');
        
        // Set month title
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        monthTitle.textContent = monthNames[currentMonth.getMonth()] + ' ' + currentMonth.getFullYear();
        
        // Clear calendar
        calendar.innerHTML = '';
        
        // Add day headers
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(day => {
            const header = document.createElement('div');
            header.className = 'calendar-day-header';
            header.textContent = day;
            calendar.appendChild(header);
        });
        
        // Get first day of month and number of days
        const firstDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
        const lastDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
        const startingDayOfWeek = firstDay.getDay();
        const monthLength = lastDay.getDate();
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Add empty cells for days before month starts
        for (let i = 0; i < startingDayOfWeek; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day disabled';
            calendar.appendChild(emptyDay);
        }
        
        // Add days of month
        for (let day = 1; day <= monthLength; day++) {
            const dayDate = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day);
            const dateString = dayDate.toISOString().split('T')[0];
            
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            
            // Check if date is in the past
            if (dayDate < today) {
                dayElement.classList.add('past');
            }
            
            // Add day number
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = day;
            dayElement.appendChild(dayNumber);
            
            // Add courses for this day
            const dayCourses = document.createElement('div');
            dayCourses.className = 'day-courses';
            
            // Find schedules for this date
            const daySchedules = filteredSchedules.filter(s => s.session_date === dateString);
            
            if (daySchedules.length > 0 && dayDate >= today) {
                daySchedules.forEach(schedule => {
                    const availableSpots = schedule.available_spots || (schedule.max_participants - schedule.current_participants);
                    
                    // Skip fully booked sessions
                    if (availableSpots <= 0) return;
                    
                    const courseItem = document.createElement('div');
                    courseItem.className = 'course-item';
                    courseItem.dataset.scheduleId = schedule.session_id;
                    courseItem.dataset.courseId = schedule.course_id;
                    courseItem.dataset.campusId = schedule.campus_id;
                    courseItem.dataset.date = dateString;
                    
                    courseItem.innerHTML = `
                        <div class="course-name">${schedule.course_name}</div>
                        <div class="course-location">${schedule.campus_name}</div>
                        <div class="course-spots">${availableSpots} left</div>
                    `;
                    
                    courseItem.addEventListener('click', function(e) {
                        e.stopPropagation();
                        selectCourse(schedule.session_id, schedule.course_id, schedule.campus_id, dateString, courseItem);
                    });
                    
                    dayCourses.appendChild(courseItem);
                });
            } else if (dayDate >= today) {
                const noCourses = document.createElement('div');
                noCourses.className = 'no-courses';
                noCourses.textContent = 'No sessions';
                dayCourses.appendChild(noCourses);
            }
            
            dayElement.appendChild(dayCourses);
            calendar.appendChild(dayElement);
        }
    }
    
    function selectCourse(scheduleId, courseId, campusId, dateString, element) {
        // Remove previous selection
        document.querySelectorAll('.course-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Add selection to clicked course
        element.classList.add('selected');
        
        // Update hidden inputs and enable next button
        document.getElementById('selectedScheduleId').value = scheduleId;
        document.getElementById('selectedCourseId').value = courseId;
        document.getElementById('selectedCampusId').value = campusId;
        document.getElementById('selectedDate').value = dateString;
        document.getElementById('nextStepBtn').disabled = false;
        selectedScheduleId = scheduleId;
        
        // Update message
        document.getElementById('selectionMessage').innerHTML = 
            `✓ <strong>Selected!</strong> Click "Continue to Personal Details" to proceed.`;
        document.getElementById('selectionMessage').style.background = '#c8e6c9';
        document.getElementById('selectionMessage').style.borderLeftColor = '#4caf50';
        document.getElementById('selectionMessage').style.color = '#2e7d32';
    }
    
    function showNoSchedules() {
        // Not needed anymore as courses are in calendar
    }
    
    function showError(message) {
        // Not needed anymore as courses are in calendar
    }
    
    function addDocumentField() {
        const container = document.getElementById('additionalDocs');
        const div = document.createElement('div');
        div.style.marginTop = '15px';
        div.innerHTML = `
            <label class="form-label" style="font-weight: 500; margin-bottom: 5px;">Document Type</label>
            <select name="document_type[]" class="form-select" style="margin-bottom: 10px;">
                <option value="id_card">ID Card</option>
                <option value="license">License</option>
                <option value="other">Other</option>
            </select>
            <input type="file" name="documents[]" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        `;
        container.appendChild(div);
    }
    </script>
</body>
</html>