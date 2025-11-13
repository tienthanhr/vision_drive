<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

// Get session ID from URL
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

// Handle messages
$successMessage = isset($_GET['success']) ? $_GET['success'] : '';
$errorMessage = isset($_GET['error']) ? $_GET['error'] : '';

if ($sessionId <= 0) {
    header('Location: admin-schedules.php?error=' . urlencode('Invalid session ID'));
    exit();
}

try {
    $db = new VisionDriveDatabase();
    $sessionDetails = $db->getSessionDetails($sessionId);
    $enrolledStudents = $db->getEnrolledStudentsBySession($sessionId);
    
    if (!$sessionDetails) {
        header('Location: admin-schedules.php?error=' . urlencode('Session not found'));
        exit();
    }
} catch (Exception $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
    $sessionDetails = null;
    $enrolledStudents = [];
}

// Format date and time for display
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// Get status badge class
function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'confirmed':
            return 'status-active';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        case 'pending':
            return 'status-pending';
        default:
            return 'status-inactive';
    }
}

function getPaymentBadgeClass($status) {
    switch(strtolower($status)) {
        case 'paid':
            return 'status-active';
        case 'unpaid':
            return 'status-pending';
        case 'refunded':
            return 'status-cancelled';
        default:
            return 'status-inactive';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolled Students - Vision Drive Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/admin-styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            display: flex;
            font-family: 'Montserrat', sans-serif;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
            width: 100%;
        }
        
        .session-info-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .session-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .session-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .session-subtitle {
            font-size: 13px;
            color: #6c757d;
        }
        
        .session-stats {
            display: flex;
            gap: 15px;
            margin-top: 12px;
        }
        
        .stat-item {
            flex: 1;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
        }
        
        .stat-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .session-details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 12px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .students-table-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .student-count {
            background: #00bcd4;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .empty-state p {
            font-size: 13px;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .status-active {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #ffc107;
            color: #000;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-cancelled {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: #17a2b8;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-inactive {
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-view {
            padding: 5px 10px;
            background: #00bcd4;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-view:hover {
            background: #0097a7;
        }
        
        .btn-remove {
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00bcd4, #0097a7);
            transition: width 0.3s ease;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .page-header > div {
            flex: 1;
        }
        
        .page-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .page-header p {
            font-size: 13px;
            color: #6c757d;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .session-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .session-details-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>📋 Enrolled Students</h1>
                <p>View students enrolled in this training session</p>
            </div>
            <a href="admin-schedules.php" class="btn-back">
                ← Back to Schedules
            </a>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($sessionDetails): ?>
        <!-- Session Information Card -->
        <div class="session-info-card">
            <div class="session-info-header">
                <div>
                    <div class="session-title"><?php echo htmlspecialchars($sessionDetails['course_name']); ?></div>
                    <div class="session-subtitle">
                        Session ID: #<?php echo $sessionDetails['session_id']; ?> | 
                        <?php echo htmlspecialchars($sessionDetails['campus_name']); ?>
                    </div>
                </div>
                <div>
                    <span class="<?php echo getStatusBadgeClass($sessionDetails['status']); ?>">
                        <?php echo strtoupper($sessionDetails['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="session-stats">
                <div class="stat-item">
                    <div class="stat-label">Enrolled</div>
                    <div class="stat-value"><?php echo $sessionDetails['current_participants']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Capacity</div>
                    <div class="stat-value"><?php echo $sessionDetails['max_participants']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Available</div>
                    <div class="stat-value">
                        <?php echo $sessionDetails['max_participants'] - $sessionDetails['current_participants']; ?>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Fill Rate</div>
                    <div class="stat-value">
                        <?php 
                        $fillRate = ($sessionDetails['current_participants'] / $sessionDetails['max_participants']) * 100;
                        echo number_format($fillRate, 0) . '%'; 
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $fillRate; ?>%"></div>
            </div>
            
            <div class="session-details-grid">
                <div class="detail-item">
                    <div class="detail-label">Session Date</div>
                    <div class="detail-value"><?php echo formatDate($sessionDetails['session_date']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Time</div>
                    <div class="detail-value">
                        <?php echo formatTime($sessionDetails['start_time']); ?> - 
                        <?php echo formatTime($sessionDetails['end_time']); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Instructor</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($sessionDetails['instructor_name'] ?: 'TBA'); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Location</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($sessionDetails['city'] . ', ' . $sessionDetails['region']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="students-table-container">
            <div class="table-header">
                <div class="table-title">Enrolled Students</div>
                <div class="student-count">
                    <?php echo count($enrolledStudents); ?> Student<?php echo count($enrolledStudents) != 1 ? 's' : ''; ?>
                </div>
            </div>

            <?php if (empty($enrolledStudents)): ?>
            <div class="empty-state">
                <div style="font-size: 64px; margin-bottom: 20px;">👥</div>
                <h3>No Students Enrolled Yet</h3>
                <p>This training session doesn't have any enrolled students at the moment.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Booking Date</th>
                            <th>Confirmation Code</th>
                            <th>Booking Status</th>
                            <th>Payment</th>
                            <th>Documents</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrolledStudents as $student): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td><?php echo formatDate($student['booking_date']); ?></td>
                            <td>
                                <code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($student['confirmation_code']); ?>
                                </code>
                            </td>
                            <td>
                                <span class="<?php echo getStatusBadgeClass($student['booking_status']); ?>">
                                    <?php echo htmlspecialchars($student['booking_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="<?php echo getPaymentBadgeClass($student['payment_status']); ?>">
                                    <?php echo htmlspecialchars($student['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                                    <?php echo $student['document_count']; ?> doc<?php echo $student['document_count'] != 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin-view-student.php?id=<?php echo $student['user_id']; ?>&session_id=<?php echo $sessionId; ?>" 
                                       class="btn-view" title="View Student Details">
                                        👤 View
                                    </a>
                                    <a href="admin-remove-student-from-session.php?user_id=<?php echo $student['user_id']; ?>&session_id=<?php echo $sessionId; ?>" 
                                       class="btn-remove" title="Remove from Session">
                                        🗑️ Remove
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php if (!empty($student['notes'])): ?>
                        <tr style="background: #f8f9fa;">
                            <td colspan="9" style="padding: 10px 20px; font-size: 12px;">
                                <strong>Notes:</strong> <?php echo htmlspecialchars($student['notes']); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
