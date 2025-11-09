<?php
require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    $pdo = $db->getConnection();
    
    echo "=== HƯỚNG DẪN GÁN HỌC SINH VÀO KHÓA HỌC ===\n\n";
    
    // 1. Kiểm tra cấu trúc bảng users (students)
    echo "1. CẤU TRÚC BẢNG USERS (Students):\n";
    echo "==================================\n";
    $result = $pdo->query('DESCRIBE users');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 2. Kiểm tra cấu trúc bảng courses
    echo "\n2. CẤU TRÚC BẢNG COURSES:\n";
    echo "=========================\n";
    $result = $pdo->query('DESCRIBE courses');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 3. Kiểm tra cấu trúc bảng training_sessions
    echo "\n3. CẤU TRÚC BẢNG TRAINING_SESSIONS:\n";
    echo "===================================\n";
    $result = $pdo->query('DESCRIBE training_sessions');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 4. Kiểm tra cấu trúc bảng bookings
    echo "\n4. CẤU TRÚC BẢNG BOOKINGS:\n";
    echo "==========================\n";
    $result = $pdo->query('DESCRIBE bookings');
    while($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // 5. Hiển thị dữ liệu mẫu
    echo "\n5. DỮ LIỆU MẪU:\n";
    echo "================\n";
    
    echo "\nStudents (users với role = 'trainee'):\n";
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
    
    echo "\nBookings (học sinh đã gán vào khóa):\n";
    $result = $pdo->query("SELECT b.booking_id, u.first_name, u.last_name, c.course_name, b.status FROM bookings b JOIN users u ON b.user_id = u.user_id JOIN training_sessions ts ON b.session_id = ts.session_id JOIN courses c ON ts.course_id = c.course_id LIMIT 3");
    while($row = $result->fetch()) {
        echo "- Booking ID: {$row['booking_id']}, Student: {$row['first_name']} {$row['last_name']}, Course: {$row['course_name']}, Status: {$row['status']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>