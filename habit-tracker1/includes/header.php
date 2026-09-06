<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habit Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

<header class="header-nav">
    <div class="logo">
        <a href="dashboard.php" style="color: #fff; text-decoration: none; font-weight: bold;">Habit Tracker</a>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php" style="color: #fff; text-decoration: none; margin-right: 15px;">Dashboard</a>
        <a href="leaderboard.php" style="color: #fff; text-decoration: none; margin-right: 15px;">Leaderboard</a>
        <a href="profile.php" style="color: #fff; text-decoration: none; margin-right: 15px;">Profile Settings</a>
        <a href="logout.php" style="color: #ef4444; text-decoration: none;">Logout</a>
    </nav>
</header>