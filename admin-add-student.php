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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long';
        $messageType = 'error';
    } else {
        // Check if email already exists
        try {
            $stmt = $db->getConnection()->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = 'Email already exists';
                $messageType = 'error';
            } else {
                // Create new student
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->getConnection()->prepare("
                    INSERT INTO users (email, password_hash, role, first_name, last_name, phone, region, status) 
                    VALUES (?, ?, 'trainee', ?, ?, ?, ?, 'active')
                ");
                
                $result = $stmt->execute([
                    $email,
                    $passwordHash,
                    $firstName,
                    $lastName,
                    $phone,
                    $region
                ]);
                
                if ($result) {
                    header('Location: admin-students.php?success=Student created successfully');
                    exit();
                } else {
                    $message = 'Failed to create student';
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Student - Vision Drive Admin</title>
    <?php include 'includes/admin-head.php'; ?>
    <style>
        .form-container { max-width: 700px; margin: 0 auto; }
        .admin-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 768px) { .admin-form .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Add New Student</h1>
                <nav class="breadcrumb">
                    <span>Admin</span> > <a href="admin-students.php">Students</a> > Add
                </nav>
            </div>

            <div class="page-content">
                <?php if ($message): ?>
                    <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" class="admin-form">
                        <?php csrf_input(); ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="region">Region</label>
                                <select id="region" name="region">
                                    <option value="">Select Region</option>
                                    <option value="Auckland" <?= ($_POST['region'] ?? '') === 'Auckland' ? 'selected' : '' ?>>Auckland</option>
                                    <option value="Wellington" <?= ($_POST['region'] ?? '') === 'Wellington' ? 'selected' : '' ?>>Wellington</option>
                                    <option value="Christchurch" <?= ($_POST['region'] ?? '') === 'Christchurch' ? 'selected' : '' ?>>Christchurch</option>
                                    <option value="Hamilton" <?= ($_POST['region'] ?? '') === 'Hamilton' ? 'selected' : '' ?>>Hamilton</option>
                                    <option value="Tauranga" <?= ($_POST['region'] ?? '') === 'Tauranga' ? 'selected' : '' ?>>Tauranga</option>
                                    <option value="Dunedin" <?= ($_POST['region'] ?? '') === 'Dunedin' ? 'selected' : '' ?>>Dunedin</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required minlength="6">
                                <small class="form-help">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Student
                            </button>
                            <a href="admin-students.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Students
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>