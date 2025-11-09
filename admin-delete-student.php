<?php
session_start();

// Admin auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// CSRF validation
$csrf = $_GET['csrf'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    header('Location: admin-students.php?error=' . urlencode('Invalid CSRF token'));
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin-students.php?error=' . urlencode('Invalid student id'));
    exit();
}

require_once __DIR__ . '/config/database.php';

try {
    $db = new VisionDriveDatabase();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
    $ok = $stmt->execute([$id]);
    if ($ok && $stmt->rowCount() > 0) {
        header('Location: admin-students.php?success=' . urlencode('Student deactivated'));
        exit();
    } else {
        header('Location: admin-students.php?error=' . urlencode('No changes made'));
        exit();
    }
} catch (Exception $e) {
    header('Location: admin-students.php?error=' . urlencode('Operation failed'));
    exit();
}
