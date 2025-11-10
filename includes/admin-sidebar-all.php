<?php
// Get current filename to highlight active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Admin Chung -->
<aside class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <img src="images/Logo(Nav).png" alt="Vision Drive" style="height: 40px; margin: 20px auto; display: block;">
    </div>
    
    <!-- Menu Navigation -->
    <ul class="sidebar-menu">
        <li>
            <a href="admin-dashboard.php" class="<?= $current_page == 'admin-dashboard.php' ? 'active' : '' ?>">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="admin-courses.php" class="<?= $current_page == 'admin-courses.php' ? 'active' : '' ?>">
                <span class="icon">📚</span>
                <span>Courses</span>
            </a>
        </li>
        <li>
            <a href="admin-campuses.php" class="<?= $current_page == 'admin-campuses.php' ? 'active' : '' ?>">
                <span class="icon">🏫</span>
                <span>Campuses</span>
            </a>
        </li>
        <li>
            <a href="admin-schedules.php" class="<?= $current_page == 'admin-schedules.php' ? 'active' : '' ?>">
                <span class="icon">📅</span>
                <span>Schedules</span>
            </a>
        </li>
        <li>
            <a href="admin-students.php" class="<?= $current_page == 'admin-students.php' ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Students & Documents</span>
            </a>
        </li>
        <li style="margin-top: 30px;">
            <a href="logout.php" class="logout">
                <span class="icon">🚪</span>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>