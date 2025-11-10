<?php
session_start();
require_once 'config/database.php';

// Handle login
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = new VisionDriveDatabase();
        $admin = $db->authenticateAdmin($username, $password);
        
        if ($admin) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['email'];
            $_SESSION['admin_id'] = $admin['user_id'];
            $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            header('Location: admin-dashboard.php');
            exit();
        } else {
            $error_message = 'Invalid login credentials';
        }
    } catch (Exception $e) {
        $error_message = 'Database connection error. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Admin Login Specific Styles */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        }

        .login-container {
            background: white;
            border-radius: 15px;
            padding: 60px 80px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .admin-title {
            font-size: 32px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 40px;
            position: relative;
            letter-spacing: 0.5px;
        }

        .admin-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: var(--primary-blue);
            margin: 15px auto 0;
        }

        .form-group {
            margin-bottom: 30px;
            text-align: left;
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

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.3s;
            background: #fafafa;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: white;
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }

        .forgot-password a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: var(--primary-blue);
        }

        .login-btn {
            background: var(--primary-blue);
            color: white;
            padding: 15px 50px;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            width: 100%;
            letter-spacing: 0.5px;
        }

        .login-btn:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
        }

        .error-message {
            background: #ffebee;
            color: var(--danger-red);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid var(--danger-red);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                padding: 40px 30px;
                margin: 20px;
            }

            .admin-title {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .admin-title {
                font-size: 24px;
            }

            .form-input {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <div class="logo-text">vision</div>
            <div class="logo-subtitle">drive</div>
        </div>
        <nav>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
        <a href="index.php" class="btn btn-primary">Home</a>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="login-container">
            <h1 class="admin-title">Admin site</h1>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">
                        Login credentials <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input"
                        placeholder="Enter your username"
                        required
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        placeholder="Enter your password"
                        required
                    >
                    <div class="forgot-password">
                        <a href="#forgot">Forgot password?</a>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">vision drive</div>
            <div class="footer-info">
                <div class="footer-item">
                    <span class="icon">📧</span>
                    <span>enquiries@visiondrive.nz</span>
                </div>
                <div class="footer-item">
                    <span class="icon">📞</span>
                    <span>0800 837 484</span>
                </div>
                <div class="footer-item">
                    <span class="icon">📍</span>
                    <span>21 Ruakura Road, Hamilton East, 3216</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
