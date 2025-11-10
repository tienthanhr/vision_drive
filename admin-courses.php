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
    $status = $_GET['status'] ?? 'active';
    if (!in_array($status, ['active','inactive','all'])) { $status = 'active'; }
    $courses = $db->getCourses($status);
} catch (Exception $e) {
    // Fallback data
    $courses = [
        ['id' => 1, 'name' => 'Forklift Operator', 'description' => 'Basic forklift operation training', 'duration' => '8 hours', 'price' => 350, 'max_capacity' => 10, 'status' => 'active'],
        ['id' => 2, 'name' => 'Forklift Refresher', 'description' => 'Refresher course for experienced operators', 'duration' => '4 hours', 'price' => 180, 'max_capacity' => 10, 'status' => 'active'],
        ['id' => 3, 'name' => 'Class 2 Truck', 'description' => 'Heavy vehicle training course', 'duration' => '16 hours', 'price' => 750, 'max_capacity' => 10, 'status' => 'active']
    ];
}

// Bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Location: admin-courses.php?error=' . urlencode('Invalid CSRF token'));
        exit();
    }
    $action = $_POST['bulk_action'];
    $ids = $_POST['selected_ids'] ?? [];
    if (!empty($ids) && is_array($ids)) {
        $deleted = 0;
        if ($action === 'delete') {
            foreach ($ids as $cid) {
                $cid = intval($cid);
                if ($cid > 0) {
                    try { if ($db->deleteCourse($cid)) { $deleted++; } } catch (Exception $e) {}
                }
            }
            header('Location: admin-courses.php?success=' . urlencode("Deleted {$deleted} course(s)"));
        } elseif ($action === 'restore') {
            $conn = $db->getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("UPDATE courses SET is_active = 1 WHERE course_id IN ($placeholders)");
            $ok = $stmt->execute(array_map('intval', $ids));
            $count = $ok ? $stmt->rowCount() : 0;
            header('Location: admin-courses.php?success=' . urlencode("Restored {$count} course(s)"));
        }
        exit();
    } else {
        header('Location: admin-courses.php?error=' . urlencode('No courses selected'));
        exit();
    }
}

// Handle search
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $courses = array_filter($courses, function($course) use ($searchTerm) {
        return stripos($course['name'], $searchTerm) !== false || 
               stripos($course['description'], $searchTerm) !== false;
    });
}

// Sorting
$allowedSorts = [
    'id' => 'id',
    'name' => 'name',
    'duration' => 'duration',
    'max_capacity' => 'max_capacity',
    'price' => 'price',
    'status' => 'status'
];
$sort = $_GET['sort'] ?? '';
$order = strtolower($_GET['order'] ?? 'asc');
if (isset($allowedSorts[$sort])) {
    $key = $allowedSorts[$sort];
    usort($courses, function($a, $b) use ($key, $order) {
        $va = $a[$key] ?? '';
        $vb = $b[$key] ?? '';
        // Normalize numeric compare for id/price/max_capacity
        if (in_array($key, ['id','price','max_capacity'])) {
            $va = floatval($va);
            $vb = floatval($vb);
        }
        if ($va == $vb) return 0;
        $cmp = ($va < $vb) ? -1 : 1;
        return $order === 'desc' ? -$cmp : $cmp;
    });
}

// Messages
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'course_deleted':
            $success_message = '🎉 Course deleted successfully!';
            break;
        case 'Course deleted successfully':
            $success_message = '✅ Course has been deleted successfully!';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_id':
            $error_message = '❌ Invalid course ID';
            break;
        case 'course_not_found':
            $error_message = '⚠️ Course not found';
            break;
        case 'delete_failed':
            $error_message = '❌ Failed to delete course';
            break;
        case 'database_error':
            $error_message = '🚫 Database error occurred';
            break;
        case 'Failed to delete course':
            $error_message = '❌ Failed to delete course - Please try again';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Course Management</title>
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
            margin-bottom: 20px;
        }

        .search-input {
            padding: 12px 40px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 300px;
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

        .courses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .courses-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid #e0e0e0;
            letter-spacing: 0.5px;
        }

        .courses-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .courses-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-active {
            background: #e8f5e8;
            color: var(--success-green);
        }

        .status-inactive {
            background: #ffebee;
            color: var(--danger-red);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .price-cell {
            font-weight: 600;
            color: var(--primary-blue);
        }

        .success-message {
            background: #e8f5e8;
            color: var(--success-green);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid var(--success-green);
        }

        .error-message {
            background: #ffebee;
            color: var(--danger-red);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid var(--danger-red);
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

            .courses-table {
                font-size: 14px;
                display: block;
                overflow-x: auto;
            }

            .courses-table th,
            .courses-table td {
                padding: 10px 8px;
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
                <h1 class="page-title">Course Management</h1>
                <p class="page-subtitle">Manage all training courses and programs</p>
            </div>

            <!-- Content Section -->
            <div class="content-section">
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
                
                <div class="section-header">
                    <h2 class="section-title">Upcoming Training Sessions</h2>
                    <a href="admin-add-course.php" class="add-btn">Add new course</a>
                </div>

                <!-- Search -->
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

                <!-- Courses Table -->
                <form method="POST" onsubmit="return confirmBulkDelete();">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="bulk_action" value="delete">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <select name="bulk_action" class="form-select form-select-sm d-inline-block w-auto">
                                    <option value="delete">Delete</option>
                                    <option value="restore">Restore</option>
                                </select>
                            </div>
                            <div>
                                <?php $status = $_GET['status'] ?? 'active'; ?>
                                <label for="statusFilter" class="form-label mb-1">Status</label>
                                <select id="statusFilter" class="form-select form-select-sm" onchange="onChangeStatusFilter(this.value)">
                                    <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
                                    <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
                                    <option value="all" <?= $status==='all'?'selected':'' ?>>All</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger mt-2">Apply</button>
                    </div>
                    <div class="table-responsive">
                <table class="courses-table table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all" onclick="toggleSelectAll(this)"></th>
                            <?php 
                                $nextOrder = $order === 'asc' ? 'desc' : 'asc';
                                $qsBase = function($col) use ($searchTerm, $nextOrder, $sort, $order) {
                                    $ord = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
                                    $q = http_build_query(array_filter([
                                        'search' => $searchTerm,
                                        'sort' => $col,
                                        'order' => $ord
                                    ], function($v){ return $v !== '' && $v !== null; }));
                                    return 'admin-courses.php' . ($q ? ('?' . $q) : '');
                                };
                            ?>
                            <th><a href="<?= $qsBase('id') ?>">ID<?= ($sort==='id'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('name') ?>">Course name<?= ($sort==='name'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('duration') ?>">Duration<?= ($sort==='duration'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('max_capacity') ?>">Max Capacity<?= ($sort==='max_capacity'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('price') ?>">Price<?= ($sort==='price'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('status') ?>">Status<?= ($sort==='status'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    No courses found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_ids[]" value="<?= $course['id'] ?>"></td>
                                    <td><?= $course['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($course['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($course['duration']) ?></td>
                                    <td><?= $course['max_capacity'] ?? 10 ?></td>
                                    <td class="price-cell">$<?= number_format($course['price']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $course['status'] ?? 'active' ?>">
                                            <?= ucfirst($course['status'] ?? 'Active') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin-edit-course.php?id=<?= $course['id'] ?>" class="action-btn btn-edit">Edit</a>
                                            <a href="admin-delete-course.php?id=<?= $course['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
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
    <script>
        function onChangeStatusFilter(val){
            const params = new URLSearchParams(window.location.search);
            if (val==='active') { params.delete('status'); } else { params.set('status', val); }
            window.location.search = params.toString();
        }
        function toggleSelectAll(master){
            document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb=>cb.checked = master.checked);
        }
        function confirmBulkDelete(){
            const any = document.querySelectorAll('input[name="selected_ids[]"]:checked').length>0;
            if(!any){
                alert('Please select at least one course to delete.');
                return false;
            }
            const action = document.querySelector('select[name="bulk_action"]').value;
            if(action==='delete') return confirm('Delete selected courses? This may deactivate courses with related sessions.');
            if(action==='restore') return confirm('Restore selected courses?');
            return true;
        }
    </script>
</body>
</html>