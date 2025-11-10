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
    $campuses = $db->getCampuses($status);
} catch (Exception $e) {
    // Fallback data
    $campuses = [
        ['id' => 1, 'name' => 'Auckland', 'location' => 'Auckland, New Zealand', 'address' => '123 Queen Street, Auckland 1010', 'phone' => '+64 9 123 4567', 'status' => 'active'],
        ['id' => 2, 'name' => 'Hamilton', 'location' => 'Hamilton, New Zealand', 'address' => '21 Ruakura Road, Hamilton East 3216', 'phone' => '+64 7 837 484', 'status' => 'active'],
        ['id' => 3, 'name' => 'Christchurch', 'location' => 'Christchurch, New Zealand', 'address' => '456 Cathedral Square, Christchurch 8011', 'phone' => '+64 3 456 7890', 'status' => 'active']
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
        header('Location: admin-campuses.php?error=' . urlencode('Invalid CSRF token'));
        exit();
    }
    $action = $_POST['bulk_action'];
    $ids = $_POST['selected_ids'] ?? [];
    if (!empty($ids) && is_array($ids)) {
        $conn = $db->getConnection();
        $affected = 0;
        if ($action === 'delete') {
            foreach ($ids as $id) {
                $cid = intval($id);
                if ($cid <= 0) continue;
                try {
                    // If campus has sessions -> set inactive, else delete
                    $st = $conn->prepare('SELECT COUNT(*) FROM training_sessions WHERE campus_id = ?');
                    $st->execute([$cid]);
                    $count = (int)$st->fetchColumn();
                    if ($count > 0) {
                        $st = $conn->prepare('UPDATE campuses SET is_active = 0 WHERE campus_id = ?');
                        if ($st->execute([$cid])) $affected++;
                    } else {
                        $st = $conn->prepare('DELETE FROM campuses WHERE campus_id = ?');
                        if ($st->execute([$cid])) $affected++;
                    }
                } catch (Exception $e) { }
            }
            header('Location: admin-campuses.php?success=' . urlencode("Processed {$affected} campus(es)"));
        } elseif ($action === 'restore') {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("UPDATE campuses SET is_active = 1 WHERE campus_id IN ($placeholders)");
            $ok = $stmt->execute(array_map('intval', $ids));
            $count = $ok ? $stmt->rowCount() : 0;
            header('Location: admin-campuses.php?success=' . urlencode("Restored {$count} campus(es)"));
        }
        exit();
    } else {
        header('Location: admin-campuses.php?error=' . urlencode('No campuses selected'));
        exit();
    }
}

// Handle search
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $campuses = array_filter($campuses, function($campus) use ($searchTerm) {
        return stripos($campus['name'], $searchTerm) !== false || 
               stripos($campus['location'], $searchTerm) !== false ||
               stripos($campus['address'], $searchTerm) !== false;
    });
}

// Sorting
$allowedSorts = [
    'id' => 'id',
    'name' => 'name',
    'location' => 'location',
    'address' => 'address',
    'phone' => 'phone',
    'status' => 'status'
];
$sort = $_GET['sort'] ?? '';
$order = strtolower($_GET['order'] ?? 'asc');
if (isset($allowedSorts[$sort])) {
    $key = $allowedSorts[$sort];
    usort($campuses, function($a, $b) use ($key, $order) {
        $va = $a[$key] ?? '';
        $vb = $b[$key] ?? '';
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
    <title>Vision Drive - Campus Management</title>
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

        .campuses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .campuses-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid #e0e0e0;
            letter-spacing: 0.5px;
        }

        .campuses-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .campuses-table tr:hover {
            background: #f8f9fa;
        }

        .campus-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .campus-location {
            color: var(--text-light);
            font-size: 14px;
        }

        .campus-address {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.4;
        }

        .campus-phone {
            color: var(--primary-blue);
            font-weight: 500;
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

            .campuses-table {
                font-size: 14px;
                display: block;
                overflow-x: auto;
            }

            .campuses-table th,
            .campuses-table td {
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
    <!-- Header -->
    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <?php include 'includes/admin-sidebar-all.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Campus Management</h1>
                <p class="page-subtitle">Manage training locations and facilities</p>
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
                    <h2 class="section-title">All Campuses</h2>
                    <a href="admin-add-campus.php" class="add-btn">Add new campus</a>
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

                <!-- Campuses Table -->
                <form method="POST" onsubmit="return confirmBulkDelete();">
                    <?php csrf_input(); ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <select name="bulk_action" class="form-select form-select-sm d-inline-block w-auto">
                                    <option value="delete">Delete</option>
                                    <option value="restore">Restore</option>
                                </select>
                            </div>
                            <div>
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
                        <table class="campuses-table table table-hover align-middle">
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
                                            return 'admin-campuses.php' . ($q ? ('?' . $q) : '');
                                        };
                                    ?>
                                    <th><a href="<?= $qsBase('id') ?>">ID<?= ($sort==='id'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                                    <th><a href="<?= $qsBase('name') ?>">Campus name<?= ($sort==='name'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                                    <th><a href="<?= $qsBase('location') ?>">Location<?= ($sort==='location'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                                    <th><a href="<?= $qsBase('address') ?>">Address<?= ($sort==='address'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                                    <th><a href="<?= $qsBase('phone') ?>">Phone<?= ($sort==='phone'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                                    <th><a href="<?= $qsBase('status') ?>">Status<?= ($sort==='status'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($campuses)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-light);">
                                            No campuses found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($campuses as $campus): ?>
                                        <tr>
                                            <td><input type="checkbox" name="selected_ids[]" value="<?= $campus['id'] ?>"></td>
                                            <td><?= $campus['id'] ?></td>
                                            <td>
                                                <div class="campus-name"><?= htmlspecialchars($campus['name']) ?></div>
                                            </td>
                                            <td>
                                                <div class="campus-location"><?= htmlspecialchars($campus['location']) ?></div>
                                            </td>
                                            <td>
                                                <div class="campus-address"><?= htmlspecialchars($campus['address'] ?? $campus['location']) ?></div>
                                            </td>
                                            <td>
                                                <div class="campus-phone"><?= htmlspecialchars($campus['phone'] ?? 'N/A') ?></div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= $campus['status'] ?? 'active' ?>">
                                                    <?= ucfirst($campus['status'] ?? 'Active') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="admin-edit-campus.php?id=<?= $campus['id'] ?>" class="action-btn btn-edit">Edit</a>
                                                    <a href="admin-delete-campus.php?id=<?= $campus['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this campus?')">Delete</a>
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
                alert('Please select at least one campus to process.');
                return false;
            }
            const action = document.querySelector('select[name="bulk_action"]').value;
            if(action==='delete') return confirm('Delete/Deactivate selected campuses? Campuses with sessions will be deactivated.');
            if(action==='restore') return confirm('Restore selected campuses?');
            return true;
        }
    </script>
</body>
</html>