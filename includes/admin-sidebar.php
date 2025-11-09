<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="images/Logo(Nav).png" alt="Vision Drive" style="height: 40px; margin: 20px auto; display: block;">
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="admin-dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php' ? 'active' : '' ?>">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="admin-courses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin-courses.php' ? 'active' : '' ?>">
                <span class="icon">📚</span>
                <span>Courses</span>
            </a>
        </li>
        <li>
            <a href="admin-campuses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin-campuses.php' ? 'active' : '' ?>">
                <span class="icon">🏢</span>
                <span>Campuses</span>
            </a>
        </li>
        <li>
            <a href="admin-schedules.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin-schedules.php' ? 'active' : '' ?>">
                <span class="icon">📅</span>
                <span>Schedules</span>
            </a>
        </li>
        <li>
            <a href="admin-students.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin-students.php' ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Students & Documents</span>
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