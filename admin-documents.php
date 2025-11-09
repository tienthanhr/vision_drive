<?php
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Lấy danh sách documents từ thư mục uploads/documents
$documentsPath = 'uploads/documents/';
$documents = [];

// Ensure CSRF token exists for this session (in case head include hasn't run yet)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF for any POST action on this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Location: admin-documents.php?error=' . urlencode('Invalid CSRF token'));
        exit();
    }
}

// Bulk delete files
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['bulk_action'] ?? '') === 'delete') {
    $names = $_POST['selected_names'] ?? [];
    $deleted = 0;
    if (!empty($names) && is_array($names)) {
        foreach ($names as $n) {
            $safe = basename($n);
            $path = $documentsPath . $safe;
            if (is_file($path)) {
                if (@unlink($path)) { $deleted++; }
            }
        }
        header('Location: admin-documents.php?success=' . urlencode("Deleted {$deleted} document(s)"));
        exit();
    } else {
        header('Location: admin-documents.php?error=' . urlencode('No documents selected'));
        exit();
    }
}

if (is_dir($documentsPath)) {
    $files = scandir($documentsPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $documentsPath . $file;
            if (is_file($filePath)) {
                $documents[] = [
                    'id' => count($documents) + 1,
                    'name' => $file,
                    'type' => pathinfo($file, PATHINFO_EXTENSION),
                    'size' => filesize($filePath),
                    'upload_date' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'uploaded_by' => 'Admin',
                    'category' => 'General',
                    'status' => 'active'
                ];
            }
        }
    }
}

// Fallback data nếu không có files
if (empty($documents)) {
    $documents = [
        [
            'id' => 1,
            'name' => 'Forklift_Safety_Manual.pdf',
            'type' => 'pdf',
            'size' => 2048576,
            'upload_date' => '2025-01-15 10:30:00',
            'uploaded_by' => 'Admin',
            'category' => 'Training Materials',
            'status' => 'active'
        ],
        [
            'id' => 2,
            'name' => 'Student_Registration_Form.docx',
            'type' => 'docx',
            'size' => 524288,
            'upload_date' => '2025-01-20 14:15:00',
            'uploaded_by' => 'Admin',
            'category' => 'Forms',
            'status' => 'active'
        ],
        [
            'id' => 3,
            'name' => 'Course_Schedule_Template.xlsx',
            'type' => 'xlsx',
            'size' => 1048576,
            'upload_date' => '2025-02-01 09:45:00',
            'uploaded_by' => 'Admin',
            'category' => 'Templates',
            'status' => 'active'
        ]
    ];
}

// Xử lý search
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $documents = array_filter($documents, function($document) use ($searchTerm) {
        return stripos($document['name'], $searchTerm) !== false || 
               stripos($document['category'], $searchTerm) !== false ||
               stripos($document['uploaded_by'], $searchTerm) !== false;
    });
}

// Messages
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) { $success_message = $_GET['success']; }
if (isset($_GET['error'])) { $error_message = $_GET['error']; }

// Sorting
$allowedSorts = [
    'name' => 'name',
    'size' => 'size',
    'date' => 'upload_date',
    'uploaded_by' => 'uploaded_by',
    'category' => 'category',
    'status' => 'status'
];
$sort = $_GET['sort'] ?? '';
$order = strtolower($_GET['order'] ?? 'asc');
if (isset($allowedSorts[$sort])) {
    $key = $allowedSorts[$sort];
    usort($documents, function($a, $b) use ($key, $order) {
        $va = $a[$key] ?? '';
        $vb = $b[$key] ?? '';
        if (in_array($key, ['size'])) { $va = intval($va); $vb = intval($vb); }
        if ($va == $vb) return 0;
        $cmp = ($va < $vb) ? -1 : 1;
        return $order === 'desc' ? -$cmp : $cmp;
    });
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Function to get file icon
function getFileIcon($type) {
    switch (strtolower($type)) {
        case 'pdf':
            return '📄';
        case 'doc':
        case 'docx':
            return '📝';
        case 'xls':
        case 'xlsx':
            return '📊';
        case 'ppt':
        case 'pptx':
            return '📋';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return '🖼️';
        case 'zip':
        case 'rar':
            return '🗜️';
        default:
            return '📁';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Vision Drive - Document Management</title>
    <?php include 'includes/admin-head.php'; ?>
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

        .breadcrumb {
            color: var(--text-light);
            font-size: 16px;
        }

        .breadcrumb a {
            color: var(--primary-blue);
            text-decoration: none;
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

        .upload-btn {
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

        .upload-btn:hover {
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

        .documents-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .documents-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid #e0e0e0;
            letter-spacing: 0.5px;
            font-size: 14px;
        }

        .documents-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 14px;
        }

        .documents-table tr:hover {
            background: #f8f9fa;
        }

        .document-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-icon {
            font-size: 24px;
            width: 32px;
            text-align: center;
        }

        .document-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .document-type {
            color: var(--text-light);
            font-size: 12px;
            text-transform: uppercase;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 2px;
        }

        .file-size {
            color: var(--text-light);
            font-family: monospace;
        }

        .upload-date {
            color: var(--text-light);
        }

        .uploader-name {
            color: var(--primary-blue);
            font-weight: 500;
        }

        .category-badge {
            background: #e3f2fd;
            color: var(--primary-blue);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
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
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .btn-download {
            background: var(--success-green);
            color: white;
        }

        .btn-download:hover {
            background: #45a049;
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

            .documents-table {
                font-size: 12px;
            }

            .documents-table th,
            .documents-table td {
                padding: 8px 6px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .document-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
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
                    <a href="admin-courses.php">
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
                        <span>Students</span>
                    </a>
                </li>
                <li>
                    <a href="admin-documents.php" class="active">
                        <span class="icon">📄</span>
                        <span>Documents</span>
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
                <h1 class="page-title">Document Management</h1>
                <div class="breadcrumb">
                    <a href="admin-dashboard.php">Admin</a> > Documents
                </div>
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
                    <h2 class="section-title">All Documents</h2>
                    <a href="#upload-document" class="upload-btn">Upload document</a>
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

                <!-- Documents Table -->
                <form method="POST" onsubmit="return confirmBulkDelete();">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="bulk_action" value="delete">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <button type="submit" class="btn btn-sm btn-danger">Delete selected</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                <table class="documents-table table table-hover align-middle">
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
                                    return 'admin-documents.php' . ($q ? ('?' . $q) : '');
                                };
                                $sortIcon = function($col) use ($sort, $order) {
                                    if ($sort === $col) {
                                        return $order === 'asc' ? ' ▲' : ' ▼';
                                    }
                                    return '';
                                };
                            ?>
                            <th><a href="<?= $qsBase('name') ?>">Document<?= $sortIcon('name') ?></a></th>
                            <th><a href="<?= $qsBase('size') ?>">Size<?= $sortIcon('size') ?></a></th>
                            <th><a href="<?= $qsBase('date') ?>">Upload Date<?= $sortIcon('date') ?></a></th>
                            <th><a href="<?= $qsBase('uploaded_by') ?>">Uploaded By<?= $sortIcon('uploaded_by') ?></a></th>
                            <th><a href="<?= $qsBase('category') ?>">Category<?= $sortIcon('category') ?></a></th>
                            <th><a href="<?= $qsBase('status') ?>">Status<?= $sortIcon('status') ?></a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    No documents found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_names[]" value="<?= htmlspecialchars($document['name']) ?>"></td>
                                    <td>
                                        <div class="document-info">
                                            <div class="file-icon"><?= getFileIcon($document['type']) ?></div>
                                            <div>
                                                <div class="document-name"><?= htmlspecialchars($document['name']) ?></div>
                                                <div class="document-type"><?= strtoupper($document['type']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-size"><?= formatFileSize($document['size']) ?></div>
                                    </td>
                                    <td>
                                        <div class="upload-date">
                                            <?php
                                            $date = new DateTime($document['upload_date']);
                                            echo $date->format('M j, Y');
                                            ?>
                                            <br>
                                            <small style="color: var(--text-light);">
                                                <?= $date->format('H:i') ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="uploader-name"><?= htmlspecialchars($document['uploaded_by']) ?></div>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?= htmlspecialchars($document['category']) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $document['status'] ?>">
                                            <?= ucfirst($document['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="uploads/documents/<?= urlencode($document['name']) ?>" class="action-btn btn-download" download>Download</a>
                                            <a href="admin-delete-document.php?name=<?= urlencode($document['name']) ?>&csrf=<?= urlencode($_SESSION['csrf_token'] ?? '') ?>" class="action-btn btn-delete" onclick="return confirm('Delete this document? This cannot be undone.')">Delete</a>
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
        function toggleSelectAll(master){
            document.querySelectorAll('input[name="selected_names[]"]').forEach(cb=>cb.checked = master.checked);
        }
        function confirmBulkDelete(){
            const any = document.querySelectorAll('input[name="selected_names[]"]:checked').length>0;
            if(!any){
                alert('Please select at least one document.');
                return false;
            }
            return confirm('Delete selected documents from the server? This cannot be undone.');
        }
    </script>
</body>
</html>