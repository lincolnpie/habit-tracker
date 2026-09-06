<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$totalLogs = $pdo->query("SELECT COUNT(*) FROM habit_logs WHERE status = 'verified'")->fetchColumn();
$totalPoints = $pdo->query("SELECT SUM(points_awarded) FROM habit_logs")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<main class="dashboard-container">
    <a href="admin_dashboard.php" style="color:#6366f1;">&larr; Back to Admin Control Panel</a>
    <h2>System Activity Metrics</h2>

    <div class="habits-grid" style="margin-top: 20px;">
        <div class="habit-card">
            <h3>Verified Completion Logs</h3>
            <p style="font-size: 24px; font-weight: bold;"><?= $totalLogs ?></p>
        </div>
        <div class="habit-card">
            <h3>Total Points Distributed</h3>
            <p style="font-size: 24px; font-weight: bold;"><?= $totalPoints ?> PTS</p>
        </div>
    </div>
</main>

</body>
</html>