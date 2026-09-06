<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Fetch aggregate stats for quick report view
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalHabits = $pdo->query("SELECT COUNT(*) FROM habits")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Control Panel - Habit Tracker</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<header class="header-nav">
    <div class="logo"><strong>Admin Panel</strong></div>
    <div class="profile-container">
        <a href="../logout.php" style="color: #fff; text-decoration: none;">Logout</a>
    </div>
</header>

<main class="dashboard-container">
    <h1>Admin Control Panel</h1>

    <div class="habits-grid" style="margin-bottom: 30px;">
        <div class="habit-card">
            <h3>Manage Users</h3>
            <p>Total Registered Users: <strong><?= $totalUsers ?></strong></p>
            <a href="view_users.php" class="btn-primary" style="text-decoration:none; display:inline-block; margin-top:10px;">View Users</a>
        </div>

        <div class="habit-card">
            <h3>Habit Categories</h3>
            <p>Total Categories: <strong><?= $totalCategories ?></strong></p>
            <a href="manage_categories.php" class="btn-primary" style="text-decoration:none; display:inline-block; margin-top:10px;">Manage Categories</a>
        </div>

        <div class="habit-card">
            <h3>Content Moderation</h3>
            <p>Moderate user posts and habits.</p>
            <a href="moderate_content.php" class="btn-primary" style="text-decoration:none; display:inline-block; margin-top:10px;">Moderate Content</a>
        </div>

        <div class="habit-card">
            <h3>System Reports</h3>
            <p>View overall system usage metrics.</p>
            <a href="system_reports.php" class="btn-primary" style="text-decoration:none; display:inline-block; margin-top:10px;">System Reports</a>
        </div>
    </div>
</main>

</body>
</html>