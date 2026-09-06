<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin/admin_dashboard.php' : 'dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Habit Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-card">
    <h2>Admin Registration</h2>
    <p class="subtitle">Authorized Personnel Only</p>

    <form action="api/auth.php" method="POST" class="auth-form">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="role" value="admin">

        <div class="form-group">
            <label for="admin_key">Admin Passkey</label>
            <input type="password" id="admin_key" name="admin_key" placeholder="Enter security passkey" required>
        </div>

        <div class="form-group">
            <label for="username">Admin Username</label>
            <input type="text" id="username" name="username" placeholder="Choose username" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="admin@email.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-primary btn-block">Register Admin</button>
    </form>

    <div class="auth-footer">
        <p><a href="index.php">&larr; Return to Login</a></p>
    </div>
</div>

</body>
</html>