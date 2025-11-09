<?php
session_start();

echo "<h2>Session Debug</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo "<p style='color: green;'>✅ Admin is logged in!</p>";
    echo "<p>Username: " . ($_SESSION['admin_username'] ?? 'N/A') . "</p>";
    echo "<p>Admin ID: " . ($_SESSION['admin_id'] ?? 'N/A') . "</p>";
} else {
    echo "<p style='color: red;'>❌ Admin is NOT logged in</p>";
}

echo "<p><a href='admin-login.php'>Go to Login</a></p>";
echo "<p><a href='admin-dashboard.php'>Go to Dashboard</a></p>";
?>