<?php
// Common head include for admin pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper to render csrf input (optional usage)
if (!function_exists('csrf_input')) {
    function csrf_input() {
        $t = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
    }
}
?>
<!-- Meta -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
<!-- App styles -->
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="includes/admin-styles.css">
<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<!-- Bootstrap JS -->
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
