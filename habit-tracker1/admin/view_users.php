<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$users = $pdo->query("SELECT id, username, email, role, points, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<main class="dashboard-container">
    <a href="admin_dashboard.php" style="color:#6366f1;">&larr; Back to Admin Control Panel</a>
    <h2>Registered Users Management</h2>

    <div class="habit-card">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid #334155;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Username</th>
                    <th style="padding: 10px;">Email</th>
                    <th style="padding: 10px;">Role</th>
                    <th style="padding: 10px;">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid #1e293b;">
                        <td style="padding: 10px;"><?= $u['id'] ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($u['username']) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding: 10px;"><?= ucfirst($u['role']) ?></td>
                        <td style="padding: 10px;"><?= $u['points'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>