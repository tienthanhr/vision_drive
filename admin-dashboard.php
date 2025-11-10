<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    
    // Get actual statistics
    $stats = $db->getStats();
    $totalSessions = $stats['active_sessions'];
    $totalStudents = $stats['users'];
    $totalCourses = $stats['courses'];
    $monthlyBookings = $stats['bookings'];
    
    $courses = $db->getCourses();
    $campuses = $db->getCampuses();
    $trainingSessions = $db->getTrainingSessions();
    
    // Count campuses
    $totalCampuses = count($campuses);
    
} catch (Exception $e) {
    // Fallback data if database error
    $totalSessions = 0;
    $totalStudents = 0;
    $totalCourses = 0;
    $monthlyBookings = 0;
    $totalCampuses = 0;
    $trainingSessions = [];
    $trainingSessions = [
        [
            'course_name' => 'Forklift Operator',
            'campus_name' => 'Auckland',
            'session_date' => '2024-11-01',
            'enrolled_count' => 5,
            'max_capacity' => 10,
            'status' => 'scheduled'
        ],
        [
            'course_name' => 'Forklift Refresher', 
            'campus_name' => 'Hamilton',
            'session_date' => '2025-10-21',
            'enrolled_count' => 9,
            'max_capacity' => 10,
            'status' => 'scheduled'
        ],
        [
            'course_name' => 'Class 2 Truck',
            'campus_name' => 'Christchurch', 
            'session_date' => '2025-10-21',
            'enrolled_count' => 1,
            'max_capacity' => 10,
            'status' => 'scheduled'
        ]
    ];
}

// Handle search
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $trainingSessions = array_filter($trainingSessions, function($session) use ($searchTerm) {
        return stripos($session['course_name'], $searchTerm) !== false || 
               stripos($session['campus_name'], $searchTerm) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Dashboard Specific Styles */
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

        .page-subtitle {
            color: var(--text-light);
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 10px;
            display: block;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* Training Sessions Section */
        .content-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-dark);
            letter-spacing: 0.5px;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            padding: 10px 40px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 250px;
            font-size: 14px;
            background: #f8f9fa;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: white;
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* Table Styles */
        .sessions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .sessions-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid #e0e0e0;
            letter-spacing: 0.5px;
        }

        .sessions-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .sessions-table tr:hover {
            background: #f8f9fa;
        }

        .enrollment-info {
            font-weight: 500;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-scheduled {
            background: #e8f5e8;
            color: var(--success-green);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-edit {
            background: var(--primary-blue);
            color: white;
        }

        .btn-edit:hover {
            background: var(--secondary-blue);
        }

        .btn-delete {
            background: var(--danger-red);
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 10px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .pagination button:hover {
            background: #f0f8ff;
            border-color: var(--primary-blue);
        }

        .pagination button.active {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .search-input {
                width: 100%;
            }

            .sessions-table {
                font-size: 14px;
                display: block;
                overflow-x: auto;
            }

            .sessions-table th,
            .sessions-table td {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .page-title {
                font-size: 24px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-number {
                font-size: 36px;
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
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Here's what's happening with your training center today</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $totalSessions ?></span>
                    <div class="stat-label">Total Sessions</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $totalStudents ?></span>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $totalCourses ?></span>
                    <div class="stat-label">Active Courses</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $totalCampuses ?></span>
                    <div class="stat-label">Campuses</div>
                </div>
            </div>

            <!-- Training Sessions Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Upcoming Training Sessions</h2>
                    <div class="search-container">
                        <form method="GET" style="margin: 0;">
                            <input 
                                type="text" 
                                name="search" 
                                class="search-input" 
                                placeholder="Search..."
                                value="<?= htmlspecialchars($searchTerm) ?>"
                            >
                            <span class="search-icon">🔍</span>
                        </form>
                    </div>
                </div>

                <table class="sessions-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Campus</th>
                            <th>Date</th>
                            <th>Enrolled</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trainingSessions)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    No training sessions found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trainingSessions as $session): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($session['course_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($session['campus_name']) ?></td>
                                    <td>
                                        <?php
                                        $date = new DateTime($session['session_date']);
                                        echo $date->format('M j, Y');
                                        ?>
                                    </td>
                                    <td>
                                        <span class="enrollment-info">
                                            <?= $session['enrolled_count'] ?>/<?= $session['max_participants'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $session['status'] ?>">
                                            <?= ucfirst($session['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin-edit-schedule.php?id=<?= $session['session_id'] ?>" class="action-btn btn-edit">Edit</a>
                                            <a href="admin-delete-schedule.php?id=<?= $session['session_id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this schedule?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <button disabled>≪</button>
                    <button disabled>‹</button>
                    <button class="active">1</button>
                    <button>2</button>
                    <button>3</button>
                    <button>›</button>
                    <button>≫</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>