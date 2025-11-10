<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function for CSRF input
if (!function_exists('csrf_input')) {
    function csrf_input() {
        $token = $_SESSION['csrf_token'] ?? '';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Add Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Form Specific Styles - Same as campus form */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 700px;
            margin: 0 auto;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 16px;
            letter-spacing: 0.5px;
        }

        .required {
            color: var(--danger-red);
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.3s;
            background: #fafafa;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-help {
            display: block;
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .form-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-submit {
            background: var(--primary-blue);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: var(--light-gray);
            color: var(--text-dark);
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .success-message {
            background: #e8f5e8;
            color: var(--success-green);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid var(--success-green);
            text-align: center;
        }

        .error-message {
            background: #ffebee;
            color: var(--danger-red);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid var(--danger-red);
            text-align: center;
        }

        .page-header {
            background: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Add New Student</h1>
            </div>

            <div class="page-content">
                <?php if ($message): ?>
                    <div class="<?= $messageType === 'error' ? 'error-message' : 'success-message' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <h2 class="form-title">Student Information</h2>
                    <form method="POST">
                        <?php csrf_input(); ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-input" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-input" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="region">Region</label>
                                <select id="region" name="region" class="form-select">
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
                                <label class="form-label" for="password">Password <span class="required">*</span></label>
                                <input type="password" id="password" name="password" class="form-input" required minlength="6">
                                <small class="form-help">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm Password <span class="required">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required minlength="6">
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" class="btn-submit">Create Student</button>
                            <a href="admin-students.php" class="btn-cancel">Cancel</a>
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