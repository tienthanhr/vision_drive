<?php
session_start();

// Check admin authentication
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

try {
    $db = new VisionDriveDatabase();
    $schedules = $db->getTrainingSessions();
} catch (Exception $e) {
    // Fallback data
    $schedules = [
        [
            'id' => 1,
            'course_name' => 'Forklift Operator',
            'campus_name' => 'Auckland',
            'session_date' => '2024-11-01',
            'session_time' => '09:00',
            'duration' => '8 hours',
            'instructor' => 'John Smith',
            'enrolled_count' => 5,
            'max_capacity' => 10,
            'status' => 'scheduled'
        ],
        [
            'id' => 2,
            'course_name' => 'Forklift Refresher',
            'campus_name' => 'Hamilton',
            'session_date' => '2025-10-21',
            'session_time' => '13:00',
            'duration' => '4 hours',
            'instructor' => 'Sarah Johnson',
            'enrolled_count' => 9,
            'max_capacity' => 10,
            'status' => 'scheduled'
        ],
        [
            'id' => 3,
            'course_name' => 'Class 2 Truck',
            'campus_name' => 'Christchurch',
            'session_date' => '2025-10-21',
            'session_time' => '08:30',
            'duration' => '16 hours',
            'instructor' => 'Mike Wilson',
            'enrolled_count' => 1,
            'max_capacity' => 10,
            'status' => 'scheduled'
        ]
    ];
}

// Messages
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) { $success_message = $_GET['success']; }
if (isset($_GET['error'])) { $error_message = $_GET['error']; }

// Bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Location: admin-schedules.php?error=' . urlencode('Invalid CSRF token'));
        exit();
    }
    $ids = $_POST['selected_ids'] ?? [];
    if (!empty($ids) && is_array($ids)) {
        try {
            $conn = $db->getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM training_sessions WHERE session_id IN ($placeholders)");
            $ok = $stmt->execute(array_map('intval', $ids));
            $count = $ok ? $stmt->rowCount() : 0;
            header('Location: admin-schedules.php?success=' . urlencode("Deleted {$count} schedule(s)"));
            exit();
        } catch (Exception $e) {
            header('Location: admin-schedules.php?error=' . urlencode('Bulk delete failed'));
            exit();
        }
    } else {
        header('Location: admin-schedules.php?error=' . urlencode('No schedules selected'));
        exit();
    }
}

// Handle search
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $schedules = array_filter($schedules, function($schedule) use ($searchTerm) {
        return stripos($schedule['course_name'], $searchTerm) !== false || 
               stripos($schedule['campus_name'], $searchTerm) !== false ||
               stripos($schedule['instructor'], $searchTerm) !== false;
    });
}

// Sorting
$allowedSorts = [
    'id' => 'session_id',
    'course' => 'course_name',
    'campus' => 'campus_name',
    'date' => 'session_date',
    'start_time' => 'start_time',
    'instructor' => 'instructor_name',
    'enrolled' => 'enrolled_count',
    'status' => 'status'
];
$sort = $_GET['sort'] ?? '';
$order = strtolower($_GET['order'] ?? 'asc');
if (isset($allowedSorts[$sort])) {
    $key = $allowedSorts[$sort];
    usort($schedules, function($a, $b) use ($key, $order) {
        $va = $a[$key] ?? '';
        $vb = $b[$key] ?? '';
        if (in_array($key, ['session_id','enrolled_count'])) {
            $va = intval($va);
            $vb = intval($vb);
        }
        if ($va == $vb) return 0;
        $cmp = ($va < $vb) ? -1 : 1;
        return $order === 'desc' ? -$cmp : $cmp;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Schedule Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Page Specific Styles */
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

        .add-btn {
            background: var(--primary-blue);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .add-btn:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
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

        .schedules-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .schedules-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid #e0e0e0;
            letter-spacing: 0.5px;
            font-size: 14px;
        }

        .schedules-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 14px;
        }

        .schedules-table tr:hover {
            background: #f8f9fa;
        }

        .course-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .session-datetime {
            color: var(--text-dark);
            font-weight: 500;
        }

        .session-time {
            color: var(--text-light);
            font-size: 13px;
        }

        .instructor-name {
            color: var(--primary-blue);
            font-weight: 500;
        }

        .enrollment-info {
            font-weight: 600;
        }

        .enrollment-full {
            color: var(--danger-red);
        }

        .enrollment-available {
            color: var(--success-green);
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

        .status-completed {
            background: #e3f2fd;
            color: var(--primary-blue);
        }

        .status-cancelled {
            background: #ffebee;
            color: var(--danger-red);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .search-input {
                width: 100%;
            }

            .schedules-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
            }

            .schedules-table th,
            .schedules-table td {
                padding: 8px 6px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
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
                <h1 class="page-title">Schedule Management</h1>
                <p class="page-subtitle">Manage training sessions and schedules</p>
            </div>

            <!-- Content Section -->
            <div class="content-section">
                <?php if ($success_message): ?>
                    <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <div class="section-header">
                    <h2 class="section-title">Training Schedules</h2>
                    <div style="display: flex; gap: 15px; align-items: center;">
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
                        <a href="admin-add-schedule.php" class="add-btn">Add new schedule</a>
                    </div>
                </div>
                    </form>
                </div>

                <!-- Schedules Table -->
                <form method="POST" onsubmit="return confirmBulkDelete();">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="bulk_action" value="delete">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <button type="submit" class="btn btn-sm btn-danger">Delete selected</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                <table class="schedules-table table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all" onclick="toggleSelectAll(this)"></th>
                            <?php 
                                $qsBase = function($col) use ($searchTerm, $sort, $order) {
                                    $ord = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
                                    $q = http_build_query(array_filter([
                                        'search' => $searchTerm,
                                        'sort' => $col,
                                        'order' => $ord
                                    ], function($v){ return $v !== '' && $v !== null; }));
                                    return 'admin-schedules.php' . ($q ? ('?' . $q) : '');
                                };
                            ?>
                            <th><a href="<?= $qsBase('id') ?>">ID<?= ($sort==='id'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('course') ?>">Course<?= ($sort==='course'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('campus') ?>">Campus<?= ($sort==='campus'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('date') ?>">Date & Time<?= ($sort==='date'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th>Duration</th>
                            <th><a href="<?= $qsBase('instructor') ?>">Instructor<?= ($sort==='instructor'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('enrolled') ?>">Enrolled<?= ($sort==='enrolled'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('status') ?>">Status<?= ($sort==='status'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    No schedules found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_ids[]" value="<?= $schedule['session_id'] ?>"></td>
                                    <td><?= $schedule['session_id'] ?></td>
                                    <td>
                                        <div class="course-name"><?= htmlspecialchars($schedule['course_name']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($schedule['campus_name']) ?></td>
                                    <td>
                                        <div class="session-datetime">
                                            <?php
                                            $date = new DateTime($schedule['session_date']);
                                            echo $date->format('M j, Y');
                                            ?>
                                        </div>
                                        <div class="session-time"><?= $schedule['start_time'] ?? '09:00' ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                        $start = new DateTime($schedule['start_time']);
                                        $end = new DateTime($schedule['end_time']);
                                        $duration = $start->diff($end);
                                        echo $duration->format('%h hours');
                                        ?>
                                    </td>
                                    <td>
                                        <div class="instructor-name"><?= htmlspecialchars($schedule['instructor_name'] ?? 'TBA') ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $enrolled = $schedule['enrolled_count'] ?? 0;
                                        $capacity = $schedule['max_participants'];
                                        $isFull = $enrolled >= $capacity;
                                        ?>
                                        <span class="enrollment-info <?= $isFull ? 'enrollment-full' : 'enrollment-available' ?>">
                                            <?= $enrolled ?>/<?= $capacity ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $schedule['status'] ?>">
                                            <?= ucfirst($schedule['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin-edit-schedule.php?id=<?= $schedule['session_id'] ?>" class="action-btn btn-edit">Edit</a>
                                            <a href="admin-delete-schedule.php?id=<?= $schedule['session_id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this schedule?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                    </div>
                </form>

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        function toggleSelectAll(master){
            document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb=>cb.checked = master.checked);
        }
        function confirmBulkDelete(){
            const any = document.querySelectorAll('input[name="selected_ids[]"]:checked').length>0;
            if(!any){
                alert('Please select at least one schedule.');
                return false;
            }
            return confirm('Delete selected schedules?');
        }
    </script>
</body>
</html>