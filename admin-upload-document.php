<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

$userId = $_GET['user_id'] ?? null;
$message = '';
$messageType = '';

if (!$userId) {
    header('Location: admin-students.php?error=Invalid user ID');
    exit();
}

$db = new VisionDriveDatabase();

// Get student info
try {
    $stmt = $db->getConnection()->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_id = ? AND role = 'trainee'");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: admin-students.php?error=Student not found');
        exit();
    }
} catch (Exception $e) {
    header('Location: admin-students.php?error=Database error');
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $documentType = $_POST['document_type'] ?? '';
    $file = $_FILES['document'];
    
    if (empty($documentType)) {
        $message = 'Please select document type';
        $messageType = 'error';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'File upload error';
        $messageType = 'error';
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        $message = 'File too large. Maximum size is 10MB';
        $messageType = 'error';
    } else {
        // Create uploads directory if not exists
        $uploadDir = 'uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $originalName = $file['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Save to database
            $fileSize = $file['size'];
            $mimeType = $file['type'];
            
            if ($db->uploadDocument($userId, $documentType, $originalName, $filePath, $fileSize, $mimeType)) {
                header('Location: admin-students.php?success=Document uploaded successfully');
                exit();
            } else {
                // Delete uploaded file if database insert failed
                unlink($filePath);
                $message = 'Failed to save document info to database';
                $messageType = 'error';
            }
        } else {
            $message = 'Failed to upload file';
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
    <title>Upload Document - Vision Drive Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Upload Document</h1>
                <nav class="breadcrumb">
                    <span>Admin</span> > <a href="admin-students.php">Students</a> > Upload Document
                </nav>
            </header>

            <div class="content-body">
                <?php if ($message): ?>
                    <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <div class="form-header">
                        <h2>Upload Document for <?= htmlspecialchars($student['full_name']) ?></h2>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <div class="form-group">
                            <label for="document_type">Document Type *</label>
                            <select id="document_type" name="document_type" required>
                                <option value="">Select document type</option>
                                <option value="license" <?= ($_POST['document_type'] ?? '') === 'license' ? 'selected' : '' ?>>Driver's License</option>
                                <option value="id" <?= ($_POST['document_type'] ?? '') === 'id' ? 'selected' : '' ?>>ID Card/Passport</option>
                                <option value="medical" <?= ($_POST['document_type'] ?? '') === 'medical' ? 'selected' : '' ?>>Medical Certificate</option>
                                <option value="certificate" <?= ($_POST['document_type'] ?? '') === 'certificate' ? 'selected' : '' ?>>Training Certificate</option>
                                <option value="other" <?= ($_POST['document_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="document">Select File *</label>
                            <div class="file-upload-area">
                                <input type="file" id="document" name="document" required 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"
                                       onchange="updateFileInfo(this)">
                                <div class="file-upload-text">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to select file or drag and drop</p>
                                    <small>Supported: PDF, DOC, DOCX, JPG, PNG, GIF (Max: 10MB)</small>
                                </div>
                                <div id="file-info" class="file-info" style="display: none;">
                                    <i class="fas fa-file"></i>
                                    <span id="file-name"></span>
                                    <span id="file-size"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload Document
                            </button>
                            <a href="admin-students.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Students
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            position: relative;
            transition: border-color 0.3s;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
        }
        
        .file-upload-area input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-text i {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        
        .file-upload-text p {
            margin: 0.5rem 0;
            color: #374151;
            font-weight: 500;
        }
        
        .file-upload-text small {
            color: #6b7280;
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            color: var(--primary-color);
        }
        
        .file-info i {
            font-size: 1.5rem;
        }
        
        #file-size {
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>

    <script>
        function updateFileInfo(input) {
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const uploadText = document.querySelector('.file-upload-text');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                
                fileName.textContent = file.name;
                fileSize.textContent = `(${sizeInMB} MB)`;
                
                fileInfo.style.display = 'flex';
                uploadText.style.display = 'none';
            } else {
                fileInfo.style.display = 'none';
                uploadText.style.display = 'block';
            }
        }
    </script>
</body>
</html>