<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

// Get student ID from URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if ($userId <= 0) {
    header('Location: admin-students.php?error=' . urlencode('Invalid student ID'));
    exit();
}

try {
    $db = new VisionDriveDatabase();
    $conn = $db->getConnection();
    
    // Get student details with booking info
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM users u
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: admin-students.php?error=' . urlencode('Student not found'));
        exit();
    }
    
    // Get all bookings for this student
    $stmt = $conn->prepare("
        SELECT 
            b.booking_id,
            b.booking_date,
            b.status as booking_status,
            b.payment_status,
            b.confirmation_code,
            b.notes,
            ts.session_id,
            ts.session_date,
            ts.start_time,
            ts.end_time,
            c.course_name,
            ca.campus_name
        FROM bookings b
        INNER JOIN training_sessions ts ON b.session_id = ts.session_id
        INNER JOIN courses c ON ts.course_id = c.course_id
        INNER JOIN campuses ca ON ts.campus_id = ca.campus_id
        WHERE b.user_id = ?
        ORDER BY ts.session_date DESC
    ");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll();
    
    // Get documents
    $documents = $db->getStudentDocuments($userId);
    
} catch (Exception $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
    $student = null;
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'confirmed':
        case 'active':
            return 'status-active';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
        case 'inactive':
            return 'status-cancelled';
        case 'pending':
            return 'status-pending';
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
    <title>View Student - Vision Drive Admin</title>
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
        
        .info-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 15px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .status-active {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-pending {
            background: #ffc107;
            color: #000;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-cancelled {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-completed {
            background: #17a2b8;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-inactive {
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
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
        }
        
        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #00bcd4;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background: #0097a7;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>👤 Student Details</h1>
                <p>View student information (Read-only)</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <?php if ($sessionId > 0): ?>
                    <a href="admin-view-enrolled-students.php?session_id=<?php echo $sessionId; ?>" class="btn-back">
                        ← Back to Enrolled Students
                    </a>
                <?php else: ?>
                    <a href="admin-students.php" class="btn-back">
                        ← Back to Students
                    </a>
                <?php endif; ?>
                <a href="admin-edit-student.php?id=<?php echo $userId; ?>" class="btn-edit">
                    ✏️ Edit Student
                </a>
            </div>
        </div>

        <?php if ($student): ?>
        <!-- Personal Information -->
        <div class="info-card">
            <div class="card-header">
                <div class="card-title">📋 Personal Information</div>
                <span class="<?php echo getStatusBadgeClass($student['status']); ?>">
                    <?php echo strtoupper($student['status']); ?>
                </span>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Region</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['region'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Registration Date</div>
                    <div class="info-value"><?php echo formatDate($student['created_at']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Login</div>
                    <div class="info-value">
                        <?php echo $student['last_login'] ? formatDate($student['last_login']) : 'Never'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings -->
        <div class="info-card">
            <div class="card-header">
                <div class="card-title">📅 Training Sessions & Bookings</div>
                <span style="background: #e9ecef; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                    <?php echo count($bookings); ?> Booking<?php echo count($bookings) != 1 ? 's' : ''; ?>
                </span>
            </div>
            
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 15px;">📅</div>
                    <h3>No Bookings Yet</h3>
                    <p>This student hasn't enrolled in any training sessions.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Campus</th>
                            <th>Session Date</th>
                            <th>Time</th>
                            <th>Confirmation Code</th>
                            <th>Booking Status</th>
                            <th>Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($booking['course_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['campus_name']); ?></td>
                            <td><?php echo formatDate($booking['session_date']); ?></td>
                            <td>
                                <?php echo formatTime($booking['start_time']); ?> - 
                                <?php echo formatTime($booking['end_time']); ?>
                            </td>
                            <td>
                                <code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($booking['confirmation_code']); ?>
                                </code>
                            </td>
                            <td>
                                <span class="<?php echo getStatusBadgeClass($booking['booking_status']); ?>">
                                    <?php echo htmlspecialchars($booking['booking_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="<?php echo getStatusBadgeClass($booking['payment_status']); ?>">
                                    <?php echo htmlspecialchars($booking['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin-view-enrolled-students.php?session_id=<?php echo $booking['session_id']; ?>" 
                                   style="color: #00bcd4; text-decoration: none; font-weight: 600;">
                                    View Session →
                                </a>
                            </td>
                        </tr>
                        <?php if (!empty($booking['notes'])): ?>
                        <tr style="background: #f8f9fa;">
                            <td colspan="8" style="padding: 10px 12px; font-size: 12px;">
                                <strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes']); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Documents -->
        <div class="info-card">
            <div class="card-header">
                <div class="card-title">📎 Documents</div>
                <span style="background: #e9ecef; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                    <?php echo count($documents); ?> Document<?php echo count($documents) != 1 ? 's' : ''; ?>
                </span>
            </div>
            
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 15px;">📄</div>
                    <h3>No Documents</h3>
                    <p>This student hasn't uploaded any documents yet.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td>
                                <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">
                                    <?php echo strtoupper($doc['document_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                            <td><?php echo number_format($doc['file_size'] / 1024, 2); ?> KB</td>
                            <td><?php echo formatDate($doc['uploaded_at']); ?></td>
                            <td>
                                <a href="download.php?id=<?php echo $doc['document_id']; ?>" 
                                   style="color: #00bcd4; text-decoration: none; font-weight: 600;">
                                    Download ↓
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
