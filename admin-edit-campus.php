<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

$campusId = $_GET['id'] ?? null;
$campus = null;
$message = '';
$messageType = '';

if (!$campusId) {
    header('Location: admin-campuses.php');
    exit();
}

$db = new VisionDriveDatabase();

// Get campus data for editing
try {
    $stmt = $db->getConnection()->prepare("SELECT * FROM campuses WHERE campus_id = ?");
    $stmt->execute([$campusId]);
    $campus = $stmt->fetch();
    
    if (!$campus) {
        header('Location: admin-campuses.php?error=Campus not found');
        exit();
    }
} catch (Exception $e) {
    $message = 'Error loading campus data';
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($name) || empty($city) || empty($region)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } else {
        try {
            $stmt = $db->getConnection()->prepare("UPDATE campuses SET campus_name = ?, address = ?, city = ?, region = ?, phone = ?, email = ? WHERE campus_id = ?");
            $result = $stmt->execute([$name, $address, $city, $region, $phone, $email, $campusId]);
            
            if ($result) {
                header('Location: admin-campuses.php?success=Campus updated successfully');
                exit();
            } else {
                $message = 'Failed to update campus';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
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
    <title>Edit Campus - Vision Drive Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Unified form styles (match course edit) */
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
        .form-group { margin-bottom: 20px; }
        .form-label { display:block; font-weight:600; color:var(--text-dark); margin-bottom:8px; }
        .form-input, .form-select, .form-textarea {
            width:100%; padding:12px 15px; border:2px solid #e0e0e0; border-radius:8px; background:#fafafa;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline:none; border-color:var(--primary-blue); background:white; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-buttons { display:flex; gap:15px; justify-content:center; margin-top: 20px; }
        .btn-submit { background: var(--primary-blue); color:white; padding:12px 28px; border:none; border-radius:8px; font-weight:600; }
        .btn-cancel { background: var(--light-gray); color: var(--text-dark); padding:12px 28px; border:none; border-radius:8px; font-weight:600; text-decoration:none; }
        .success-message { background:#e8f5e8; color:var(--success-green); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; }
        .error-message { background:#ffebee; color:var(--danger-red); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; }
        .page-header { background:white; padding:30px 0; margin-bottom:30px; border-bottom:1px solid #e0e0e0; }
        .page-title { font-size:32px; font-weight:700; color:var(--text-dark); margin-bottom:8px; }
        .breadcrumb a { color: var(--primary-blue); text-decoration:none; }
        @media (max-width:768px){ .form-row{ grid-template-columns:1fr; } .form-container{ padding:30px 20px; margin:20px; } .form-buttons{ flex-direction:column; } }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Edit Campus</h1>
                <div class="breadcrumb">
                    <a href="admin-dashboard.php">Admin</a> > <a href="admin-campuses.php">Campuses</a> > Edit
                </div>
            </div>

            <div class="form-container">
                <?php if ($message): ?>
                    <div class="<?= $messageType === 'error' ? 'error-message' : 'success-message' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <h2 class="form-title">Campus Information</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Campus Name *</label>
                        <input type="text" id="name" name="name" class="form-input" value="<?= htmlspecialchars($campus['campus_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" id="address" name="address" class="form-input" value="<?= htmlspecialchars($campus['address'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" id="city" name="city" class="form-input" value="<?= htmlspecialchars($campus['city'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="region" class="form-label">Region *</label>
                            <input type="text" id="region" name="region" class="form-input" value="<?= htmlspecialchars($campus['region'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-input" value="<?= htmlspecialchars($campus['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?= htmlspecialchars($campus['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn-submit">Update Campus</button>
                        <a href="admin-campuses.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>