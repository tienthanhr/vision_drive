<?php
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

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
    
    $deleted = $db->deleteCourse($courseId);
    
    if ($deleted) {
        header('Location: admin-courses.php?success=course_deleted');
    } else {
        header('Location: admin-courses.php?error=delete_failed');
    }
    
} catch (Exception $e) {
    header('Location: admin-courses.php?error=database_error');
}

exit();
?>