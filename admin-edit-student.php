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

// Get student ID from URL
$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header('Location: admin-students.php');
    exit();
}

// Get student data
try {
    $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'trainee'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: admin-students.php');
        exit();
    }
} catch (Exception $e) {
    $message = 'Error loading student data';
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $status = $_POST['status'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } else {
        try {
            // Check if email exists for other users
            $stmt = $db->getConnection()->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $student_id]);
            
            if ($stmt->fetch()) {
                $message = 'Email already exists for another user';
                $messageType = 'error';
            } else {
                // Update student data
                if (!empty($password)) {
                    // Update with password
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->getConnection()->prepare("
                        UPDATE users 
                        SET email = ?, password_hash = ?, first_name = ?, last_name = ?, phone = ?, region = ?, status = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$email, $passwordHash, $firstName, $lastName, $phone, $region, $status, $student_id]);
                } else {
                    // Update without password
                    $stmt = $db->getConnection()->prepare("
                        UPDATE users 
                        SET email = ?, first_name = ?, last_name = ?, phone = ?, region = ?, status = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$email, $firstName, $lastName, $phone, $region, $status, $student_id]);
                }
                
                $message = 'Student updated successfully';
                $messageType = 'success';
                
                // Refresh student data
                $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$student_id]);
                $student = $stmt->fetch();
            }
        } catch (Exception $e) {
            $message = 'Error updating student: ' . $e->getMessage();
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
    <title>Edit Student - Vision Drive Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background: #f8f9fa;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 40px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .breadcrumb {
            color: var(--text-light);
            font-size: 14px;
        }

        .breadcrumb a {
            color: var(--primary-blue);
            text-decoration: none;
        }

        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 40px;
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #00acc1;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #e8f5e8;
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }

        .alert-error {
            background: #ffebee;
            color: var(--danger-red);
            border-left: 4px solid var(--danger-red);
        }

        .password-note {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .form-container {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Edit Student</h1>
                <div class="breadcrumb">
                    <a href="admin-dashboard.php">Admin</a> > 
                    <a href="admin-students.php">Students</a> > Edit Student
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="region">Region</label>
                            <select id="region" name="region">
                                <option value="">Select Region</option>
                                <option value="Auckland" <?= ($student['region'] ?? '') === 'Auckland' ? 'selected' : '' ?>>Auckland</option>
                                <option value="Hamilton" <?= ($student['region'] ?? '') === 'Hamilton' ? 'selected' : '' ?>>Hamilton</option>
                                <option value="Wellington" <?= ($student['region'] ?? '') === 'Wellington' ? 'selected' : '' ?>>Wellington</option>
                                <option value="Christchurch" <?= ($student['region'] ?? '') === 'Christchurch' ? 'selected' : '' ?>>Christchurch</option>
                                <option value="Other" <?= ($student['region'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?= ($student['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($student['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password">
                        <div class="password-note">Leave blank to keep current password</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Student</button>
                        <a href="admin-students.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>