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

$message = '';
$messageType = '';

// Handle success/error messages
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = $_GET['error'];
    $messageType = 'error';
}

try {
    $db = new VisionDriveDatabase();
    $students = $db->getStudentsWithDocuments();
} catch (Exception $e) {
    // Fallback data
    $students = [
        [
            'user_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@email.com',
            'phone' => '+64 21 123 4567',
            'date_of_birth' => '1990-05-15',
            'license_number' => 'ABC123456',
            'registration_date' => '2025-01-15',
            'status' => 'active',
            'total_bookings' => 3
        ],
        [
            'user_id' => 2,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@email.com',
            'phone' => '+64 21 234 5678',
            'date_of_birth' => '1985-08-22',
            'license_number' => 'DEF789012',
            'registration_date' => '2025-02-10',
            'status' => 'active',
            'total_bookings' => 1
        ],
        [
            'user_id' => 3,
            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'email' => 'mike.johnson@email.com',
            'phone' => '+64 21 345 6789',
            'date_of_birth' => '1992-12-03',
            'license_number' => 'GHI345678',
            'registration_date' => '2025-03-05',
            'status' => 'active',
            'total_bookings' => 2
        ]
    ];
}

// Bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['selected_ids'] ?? []; 
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Location: admin-students.php?error=' . urlencode('Invalid CSRF token'));
        exit();
    }
    if (!empty($ids) && is_array($ids)) {
        try {
            $conn = $db->getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            // Soft-delete (mark inactive) to avoid breaking references
            $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id IN ($placeholders)");
            $ok = $stmt->execute(array_map('intval', $ids));
            $count = $ok ? $stmt->rowCount() : 0;
            header('Location: admin-students.php?success=' . urlencode("Deleted {$count} student(s)"));
            exit();
        } catch (Exception $e) {
            header('Location: admin-students.php?error=' . urlencode('Bulk update failed'));
            exit();
        }
    } else {
        header('Location: admin-students.php?error=' . urlencode('No students selected'));
        exit();
    }
}

// Handle search
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $students = array_filter($students, function($student) use ($searchTerm) {
        $fullName = $student['first_name'] . ' ' . $student['last_name'];
        return stripos($fullName, $searchTerm) !== false || 
               stripos($student['email'], $searchTerm) !== false ||
               stripos($student['phone'], $searchTerm) !== false ||
               stripos($student['license_number'], $searchTerm) !== false;
    });
}

// Sorting
$allowedSorts = [
    'id' => 'user_id',
    'name' => 'full_name',
    'email' => 'email',
    'phone' => 'phone',
    'region' => 'region',
    'joined' => 'created_at',
    'documents' => 'document_count',
    'status' => 'status'
];
$sort = $_GET['sort'] ?? '';
$order = strtolower($_GET['order'] ?? 'asc');
if (isset($allowedSorts[$sort])) {
    $key = $allowedSorts[$sort];
    usort($students, function($a, $b) use ($key, $order) {
        $va = $a[$key] ?? '';
        $vb = $b[$key] ?? '';
        // Coerce numeric
        if (in_array($key, ['user_id','document_count'])) {
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
    <title>Vision Drive - Student Management</title>
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

        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .students-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid #e0e0e0;
            letter-spacing: 0.5px;
            font-size: 14px;
        }

        .students-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 14px;
        }

        .students-table tr:hover {
            background: #f8f9fa;
        }

        .student-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .student-email {
            color: var(--primary-blue);
            text-decoration: none;
        }

        .student-email:hover {
            text-decoration: underline;
        }

        .student-phone {
            color: var(--text-light);
            font-family: monospace;
        }

        .license-number {
            font-family: monospace;
            background: #f0f8ff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            color: var(--primary-blue);
        }

        .registration-date {
            color: var(--text-light);
        }

        .booking-count {
            background: var(--primary-blue);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 24px;
            display: inline-block;
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
            justify-content: center;
            align-items: center;
            width: 100%;
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

            .students-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
            }

            .students-table th,
            .students-table td {
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
                <h1 class="page-title">Student Management</h1>
                <p class="page-subtitle">Manage students, documents, and training records</p>
            </div>

            <!-- Content Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Students</h2>
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
                        <a href="admin-add-student.php" class="add-btn">Add new student</a>
                    </div>
                </div>

                <!-- Students Table -->
                <form method="POST" onsubmit="return confirmBulkDelete();">
                    <?php csrf_input(); ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <input type="hidden" name="bulk_action" value="delete">
                            <button type="submit" class="btn btn-sm btn-danger">Delete selected</button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php $statusFilter = $_GET['status'] ?? 'all'; ?>
                            <label for="statusFilter" class="mb-0">Status</label>
                            <select id="statusFilter" class="form-select form-select-sm w-auto" onchange="onChangeStatusFilter(this.value)">
                                <option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All</option>
                                <option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Active</option>
                                <option value="inactive" <?= $statusFilter==='inactive'?'selected':'' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                <table class="students-table table table-hover align-middle">
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
                                    return 'admin-students.php' . ($q ? ('?' . $q) : '');
                                };
                            ?>
                            <th><a href="<?= $qsBase('id') ?>">ID<?= ($sort==='id'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('name') ?>">Name<?= ($sort==='name'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('email') ?>">Email<?= ($sort==='email'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('phone') ?>">Phone<?= ($sort==='phone'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('region') ?>">Region<?= ($sort==='region'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('joined') ?>">Joined<?= ($sort==='joined'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('documents') ?>">Documents<?= ($sort==='documents'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th><a href="<?= $qsBase('status') ?>">Status<?= ($sort==='status'? ($order==='asc'?' ▲':' ▼') : '') ?></a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    No students found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_ids[]" value="<?= $student['user_id'] ?>"></td>
                                    <td><?= $student['user_id'] ?></td>
                                    <td>
                                        <div class="student-name">
                                            <?= htmlspecialchars($student['full_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($student['email']) ?>" class="student-email">
                                            <?= htmlspecialchars($student['email']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="student-phone"><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></div>
                                    </td>
                                    <td>
                                        <div class="student-region"><?= htmlspecialchars($student['region'] ?? 'N/A') ?></div>
                                    </td>
                                    <td>
                                        <div class="registration-date">
                                            <?php
                                            if (!empty($student['created_at'])) {
                                                $date = new DateTime($student['created_at']);
                                                echo $date->format('M j, Y');
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="document-count"><?= $student['document_count'] ?? 0 ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $student['status'] ?? 'active' ?>">
                                            <?= ucfirst($student['status'] ?? 'Active') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="action-btn btn-info" onclick="toggleDocuments(<?= $student['user_id'] ?>)">
                                                Documents (<?= $student['document_count'] ?? 0 ?>)
                                            </button>
                                            <a href="admin-upload-document.php?user_id=<?= $student['user_id'] ?>" class="action-btn btn-success">Upload</a>
                                            <a href="admin-edit-student.php?id=<?= $student['user_id'] ?>" class="action-btn btn-edit">Edit</a>
                                            <a href="admin-delete-student.php?id=<?= $student['user_id'] ?>&csrf=<?= urlencode($_SESSION['csrf_token'] ?? '') ?>" class="action-btn btn-delete" onclick="return confirm('Delete this student?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Documents Row (Hidden by default) -->
                                <tr id="documents-<?= $student['user_id'] ?>" class="documents-row" style="display: none;">
                                    <td colspan="9">
                                        <div class="documents-container">
                                            <h4>Documents for <?= htmlspecialchars($student['full_name']) ?></h4>
                                            <div id="documents-list-<?= $student['user_id'] ?>">
                                                <p class="loading">Loading documents...</p>
                                            </div>
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

    <style>
        .documents-row {
            background: #f8fafc;
        }
        
        .documents-container {
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .documents-container h4 {
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .documents-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .doc-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .doc-name {
            font-weight: 500;
        }
        
        .doc-type {
            color: var(--text-light);
            font-size: 0.875rem;
        }
        
        .btn-info {
            background: #3b82f6;
            color: white;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .loading {
            color: #6b7280;
            font-style: italic;
            animation: none !important;
            border: none !important;
            width: auto !important;
            height: auto !important;
            display: block !important;
        }
    </style>

    <script>
        function toggleDocuments(userId) {
            const row = document.getElementById('documents-' + userId);
            
            if (row.style.display === 'none' || row.style.display === '') {
                // Show documents - no animation
                row.style.display = 'table-row';
                loadDocuments(userId);
            } else {
                // Hide documents - no animation
                row.style.display = 'none';
            }
        }
        
        function loadDocuments(userId) {
            const container = document.getElementById('documents-list-' + userId);
            
            // Use fetch to get documents for this user
            fetch('get-student-documents.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayDocuments(container, data.documents);
                    } else {
                        container.innerHTML = '<p class="no-documents">Error loading documents</p>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<p class="no-documents">Error loading documents</p>';
                });
        }
        
        function displayDocuments(container, documents) {
            if (documents.length === 0) {
                container.innerHTML = '<p class="no-documents">No documents found</p>';
                return;
            }
            
            let html = '<div class="documents-list">';
            documents.forEach(doc => {
                html += `
                    <div class="document-item">
                        <div class="doc-info">
                            <i class="fas fa-file"></i>
                            <span class="doc-name">${doc.file_name}</span>
                            <span class="doc-type">(${doc.document_type})</span>
                            <span class="doc-date">${doc.uploaded_at}</span>
                        </div>
                        <div class="doc-actions">
                            <a href="download.php?id=${doc.document_id}" class="btn btn-sm btn-primary">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }
    </script>
        </main> <!-- End main-content -->
    </div> <!-- End dashboard-container -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        function onChangeStatusFilter(val){
            const params = new URLSearchParams(window.location.search);
            if (val==='all') { params.delete('status'); } else { params.set('status', val); }
            window.location.search = params.toString();
        }
        function toggleSelectAll(master){
            document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb=>cb.checked = master.checked);
        }
        function confirmBulkDelete(){
            const any = document.querySelectorAll('input[name="selected_ids[]"]:checked').length>0;
            if(!any){
                alert('Please select at least one student.');
                return false;
            }
            return confirm('Delete selected students?');
        }
    </script>
</body>
</html>