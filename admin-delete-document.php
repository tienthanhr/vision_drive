<?php
session_start();

// Ensure admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Validate CSRF token via GET param for this simple action link
$csrf = $_GET['csrf'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    header('Location: admin-documents.php?error=' . urlencode('Invalid CSRF token'));
    exit();
}

$name = $_GET['name'] ?? '';
if ($name === '') {
    header('Location: admin-documents.php?error=' . urlencode('Missing document name'));
    exit();
}

// Sanitize file name and build path
$safeName = basename($name);
$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $safeName;

if (!is_file($path)) {
    header('Location: admin-documents.php?error=' . urlencode('Document not found'));
    exit();
}

if (@unlink($path)) {
    header('Location: admin-documents.php?success=' . urlencode('Document deleted'));
    exit();
}

header('Location: admin-documents.php?error=' . urlencode('Failed to delete document'));
exit();
