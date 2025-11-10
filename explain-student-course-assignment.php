<?php
require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    $pdo = $db->getConnection();
    
    echo "=== STUDENT-COURSE ASSIGNMENT GUIDE ===\n\n";
    
    // 1. Check users table structure (students)
    echo "1. USERS TABLE STRUCTURE (Students):\n";
    echo "==================================\n";
    $result = $pdo->query('DESCRIBE users');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 2. Check courses table structure
    echo "\n2. COURSES TABLE STRUCTURE:\n";
    echo "=========================\n";
    $result = $pdo->query('DESCRIBE courses');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 3. Check training_sessions table structure
    echo "\n3. TRAINING_SESSIONS TABLE STRUCTURE:\n";
    echo "===================================\n";
    $result = $pdo->query('DESCRIBE training_sessions');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 4. Check bookings table structure
    echo "\n4. BOOKINGS TABLE STRUCTURE:\n";
    echo "==========================\n";
    $result = $pdo->query('DESCRIBE bookings');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 5. Display sample data
    echo "\n5. SAMPLE DATA:\n";
    echo "================\n";
    
    echo "\nStudents (users with role = 'trainee'):\n";
    $result = $pdo->query("SELECT user_id, first_name, last_name, email FROM users WHERE role = 'trainee' LIMIT 3");
    while($row = $result->fetch()) {
        echo "- ID: {$row['user_id']}, Name: {$row['first_name']} {$row['last_name']}, Email: {$row['email']}\n";
    }
    
    echo "\nCourses:\n";
    $result = $pdo->query("SELECT course_id, course_name, duration_hours FROM courses LIMIT 3");
    while($row = $result->fetch()) {
        echo "- ID: {$row['course_id']}, Name: {$row['course_name']}, Duration: {$row['duration_hours']}h\n";
    }
    
    echo "\nTraining Sessions:\n";
    $result = $pdo->query("SELECT ts.session_id, c.course_name, ts.session_date, ts.start_time FROM training_sessions ts JOIN courses c ON ts.course_id = c.course_id LIMIT 3");
    while($row = $result->fetch()) {
        echo "- Session ID: {$row['session_id']}, Course: {$row['course_name']}, Date: {$row['session_date']} {$row['start_time']}\n";
    }
    
    echo "\nBookings (students assigned to courses):\n";
    $result = $pdo->query("SELECT b.booking_id, u.first_name, u.last_name, c.course_name, b.status FROM bookings b JOIN users u ON b.user_id = u.user_id JOIN training_sessions ts ON b.session_id = ts.session_id JOIN courses c ON ts.course_id = c.course_id LIMIT 3");
    while($row = $result->fetch()) {
        echo "- Booking ID: {$row['booking_id']}, Student: {$row['first_name']} {$row['last_name']}, Course: {$row['course_name']}, Status: {$row['status']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>