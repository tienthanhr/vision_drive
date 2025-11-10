<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $max_capacity = intval($_POST['max_capacity'] ?? 10);
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) {
        $error_message = 'Course name is required';
    } elseif (empty($description)) {
        $error_message = 'Course description is required';
    } elseif (empty($duration)) {
        $error_message = 'Duration is required';
    } elseif ($price <= 0) {
        $error_message = 'Price must be greater than 0';
    } elseif ($max_capacity <= 0) {
        $error_message = 'Max capacity must be greater than 0';
    } else {
        try {
            $db = new VisionDriveDatabase();
            $courseData = [
                'name' => $name,
                'description' => $description,
                'duration' => $duration,
                'price' => $price,
                'max_capacity' => $max_capacity,
                'status' => $status
            ];
            
            $courseId = $db->addCourse($courseData);
            if ($courseId) {
                $success_message = 'Course added successfully!';
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'Failed to add course. Please try again.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Add Course</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Form Specific Styles */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
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

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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

        .breadcrumb {
            color: var(--text-light);
            font-size: 16px;
        }

        .breadcrumb a {
            color: var(--primary-blue);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-container {
                padding: 30px 20px;
                margin: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-buttons {
                flex-direction: column;
            }

            .btn-submit, .btn-cancel {
                width: 100%;
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
        <a href="logout.php" class="btn btn-secondary">Logout</a>
    </header>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="admin-dashboard.php">
                        <span class="icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin-courses.php" class="active">
                        <span class="icon">📚</span>
                        <span>Courses</span>
                    </a>
                </li>
                <li>
                    <a href="admin-campuses.php">
                        <span class="icon">🏢</span>
                        <span>Campuses</span>
                    </a>
                </li>
                <li>
                    <a href="admin-schedules.php">
                        <span class="icon">📅</span>
                        <span>Schedules</span>
                    </a>
                </li>
                <li>
                    <a href="admin-students.php">
                        <span class="icon">👥</span>
                        <span>Students & Documents</span>
                    </a>
                </li>
                <li style="margin-top: 30px;">
                    <a href="logout.php">
                        <span class="icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Add New Course</h1>
                <div class="breadcrumb">
                    <a href="admin-dashboard.php">Admin</a> > 
                    <a href="admin-courses.php">Courses</a> > 
                    Add Course
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <h2 class="form-title">Course Information</h2>
                
                <?php if ($success_message): ?>
                    <div class="success-message">
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            Course Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input"
                            placeholder="Enter course name"
                            required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">
                            Description <span class="required">*</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="form-textarea"
                            placeholder="Enter course description"
                            required
                        ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration" class="form-label">
                                Duration <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="duration" 
                                name="duration" 
                                class="form-input"
                                placeholder="e.g., 8 hours, 2 days"
                                required
                                value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="price" class="form-label">
                                Price (NZD) <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="price" 
                                name="price" 
                                class="form-input"
                                placeholder="0.00"
                                step="0.01"
                                min="0"
                                required
                                value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_capacity" class="form-label">
                                Max Capacity <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="max_capacity" 
                                name="max_capacity" 
                                class="form-input"
                                placeholder="10"
                                min="1"
                                required
                                value="<?= htmlspecialchars($_POST['max_capacity'] ?? '10') ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">
                                Status
                            </label>
                            <select id="status" name="status" class="form-select">
                                <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-submit">Add Course</button>
                        <a href="admin-courses.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>